# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
from UserDict import UserDict

try:
    import pwd

except ImportError:
    pwd = None

#from pyfarm import logger
from pyfarm.logger import Logger, Observer
from pyfarm.jobtypes.base import job
from pyfarm.datatypes.system import OS, OperatingSystem, USER

from twisted.internet import protocol, reactor, error

# TODO: documentation
# TODO: db handling on process success/finish

class ProcessProtocol(protocol.ProcessProtocol, Logger):
    def __init__(self, process, arguments, log):
        Logger.__init__(self, self)
        self.process = process
        self.arguments = arguments
        self.observer = Observer(log)
        self.addObserver(self.observer)
    # end __init__

    def connectionMade(self):
        '''send a log message the the process log file'''
        self.transport.write(" ".join(self.arguments))
        self.transport.closeStdin()
        self.debug('process started')
    # end connectionMade

    def outReceived(self, data):
        self.debug(data.strip(), level='STDOUT')
    # end outReceived

    def errReceived(self, data):
        self.debug(data.strip(), level='STDERR')
    # end errReceived

    def processExited(self, reason):
        exit_code = reason.value.exitCode
        args = (self.process.pid, exit_code)
        self.info("process %s exited with status %s" % args)

        if exit_code != 0:
            args = (self.process.command, exit_code, self.process.pid)
            self.error("'%s' failed (exit %s, pid %s)" % args)

        self.process.postexit(self)
        self.observer.stop()
    # end processExited
# end ProcessProtocol


class Process(Logger):
    '''
    wraps the process protocol

    '''
    def __init__(self, command, args, environ, log, user=None):
        Logger.__init__(self, self)
        self._command = command
        self._args = args
        self.user = user or USER
        self.process = None
        self.environ = {}
        self.command = " ".join(args)
        self.protocol = ProcessProtocol(self, self._args, log)

        # populate the initial environment from the
        # incoming dictionary
        if isinstance(environ, (dict, UserDict)):
            self.environ.update(environ)

        # Update the environment with the current environment to make
        # sure we're not missing any required variables.  This should
        # also take care of a bug on the windows implementation
        # of spawnProcess that causes win32 programs to crash when the
        # environment is not populated properly
        self.environ.update(os.environ.copy())

        # retrieve the uid/gid entries
        if OS in (OperatingSystem.LINUX, OperatingSystem.MAC) and pwd:
            entry = pwd.getpwnam(self.user)
            self.uid = entry.pw_uid
            self.gid = entry.pw_gid
        else:
            self.uid = None
            self.gid = None

        # construct the arguments and keywords for spawn process
        self.args = [self.protocol, self._command]
        self.kwargs = {
            'args' : self._args,
            'env' : self.environ.copy()
        }

        if OS in (OperatingSystem.LINUX, OperatingSystem.MAC) and pwd:
            # only add uid/gid if they differ from the current
            # user's id and group
            entry = pwd.getpwnam(USER)
            if entry.pw_uid != self.uid and entry.pwd_gid != self.gid:
                self.kwargs.update(uid=self.uid, gid=self.gid)
            else:
                msg = "no need to change uid/gid, they are the same as "
                msg += "the current user's group and id"
                self.debug(msg)
    # end __init__

    def prestart(self):
        '''
        Called before the command has started and can be overridden
        by subclasses looking to define a behavior after starting.
        '''
        pass
    # end prestart

    def poststart(self):
        '''
        Called after the command has started and can be overridden
        by subclasses looking to define a behavior after starting.
        '''
        pass
    # end poststart

    def postexit(self, protocol):
        '''
        Called after the process has exited and should be overridden
        by subclasses looking to define a behavior after the process exits
        '''
        pass
    # end postexit

    def start(self):
        if self.process is None:
            self.debug("calling prerun")
            self.prestart()
            self.debug('running: %s' % self.command)

            # try to spawn the process though it's
            # possible this may fail if we don't have permission
            # to do something like setuid
            try:
                self.process = reactor.spawnProcess(*self.args, **self.kwargs)

            except OSError, error:
                e = "Failed to spawn process! This most likely because "
                e += "we could not setuid, original error was: %s" % error
                self.error(e)
                raise

            self.pid = self.process.pid
            self.info("process %s started" % self.pid)
            self.debug("calling poststart")
            self.poststart()
        else:
            self.warning("process already started (pid %s)" % self.pid)
    # end start

    def signal(self, signal):
        try:
            self.warning("sending SIG%s signal to %s" % (signal, self.pid))
            self.process.signalProcess(signal)

        except error.ProcessExitedAlready:
            self.warning("process %s has already stopped" % self.pid)
    # end signal

    def stop(self, wait=False):
        '''sends SIGHUP to the process asking it to terminate'''
        self.signal('HUP')
    # end stop

    def kill(self):
        '''sends a SIGKILL to the process informing it to terminate immediately'''
        self.signal('KILL')
    # end kill
# end Process


class ProcessFrame(Process):
    '''
    Wraps Process when provided input based on the frame id.  This class
    should be inherited by any job class looking to setup and
    run a command.  See the methods on the Process class to determine
    prerun, postrun, and exit behaviors.
    '''
    def __init__(self, frame):
        self.frame = frame

        if not isinstance(self.frame, job.BaseJob):
            args = (self.frame, job.BaseJob)
            raise TypeError("expected %s to be an instance of %s" % args)

        Process.__init__(self,
            self.frame.command, self.frame.args,
            self.frame.environ, self.frame.observer,
            user=self.frame.user
        )

    # end __init__
# end ProcessRow
