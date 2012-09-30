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
import logging
from UserDict import UserDict

try:
    import pwd

except ImportError:
    pwd = None

from pyfarm import logger
from pyfarm.datatypes.system import OS, OperatingSystem, USER

from twisted.internet import protocol, reactor, error
from twisted.python import log

# TODO: documentation
# TODO: db handling on process success/finish

class ProcessProtocol(protocol.ProcessProtocol):
    def __init__(self, process, arguments, observer):
        self.process = process
        self.observer = observer
        self.arguments = arguments
    # end __init__

    def log(self, msg, **kwargs):
        if kwargs.get('parent'):
            self.process._log(msg, **kwargs)

        kwargs.setdefault('filepath', self.observer.stream.name)
        log.msg(msg, **kwargs)
# end log

    def connectionMade(self):
        '''send a log message the the process log file'''
        self.transport.write(" ".join(self.arguments))
        self.transport.closeStdin()
        self.log('process started')
    # end connectionMade

    def outReceived(self, data):
        self.log(data.strip(), level='STDOUT')
    # end outReceived

    def errReceived(self, data):
        self.log(data.strip(), level='STDERR')
    # end errReceived

    def processExited(self, reason):
        exit_code = reason.value.exitCode
        args = (self.process.pid, exit_code)
        self.log(
            "process %s exited with status %s" % args, parent=True,
            level=logging.INFO
        )

        if exit_code != 0:
            args = (self.process.command, exit_code, self.process.pid)
            self.log(
                "'%s' failed (exit %s, pid %s)" % args,
                level=logging.ERROR,
                parent=True
            )

        self.observer.stop()
    # end processExited
# end ProcessProtocol


class Process(object):
    '''wraps the process protocol'''
    def __init__(self, command, args, environ, log, user=None):
        self._command = command
        self._args = args
        self.user = user or USER
        self.process = None
        self.environ = {}
        self.command = "%s %s" % (command, " ".join(args))
        self.observer = logger.Observer(log)
        self.observer.start()
        self.protocol = ProcessProtocol(self, self._args, self.observer)

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
                self._log(msg)
    # end __init__

    def log(self, msg, **kwargs):
        '''send a log message the the process log file'''
        kwargs.setdefault('filepath', self.observer.stream.name)
        log.msg(msg, **kwargs)
    # end log

    def _log(self, msg, **kwargs):
        kwargs.setdefault('system', self.__class__.__name__)
        log.msg(msg, **kwargs)
    # end _log

    def start(self):
        if self.process is None:
            self.log('running: %s' % self.command)

            # try to spawn the process though it's
            # possible this may fail if we don't have permission
            # to do something like setuid
            try:
                self.process = reactor.spawnProcess(*self.args, **self.kwargs)

            except OSError, error:
                e = "Failed to spawn process! This most likely because "
                e += "we could not setuid, original error was: %s" % error
                self._log(e, level=logging.ERROR)
                raise

            self.pid = self.process.pid
            self._log(
                'process %s started' % self.pid,
                level=logging.INFO
            )
        else:
            self._log(
                'process already started (pid %s)' % self.pid,
                level=logging.WARNING
            )
    # end start

    def signal(self, signal):
        try:
            self._log(
                "sending SIG%s signal to %s" % (signal, self.pid),
                level=logging.WARNING
            )
            self.process.signalProcess(signal)

        except error.ProcessExitedAlready:
            self._log(
                "process %s has already stopped" % self.pid,
                level=logging.WARNING
            )
    # end signal

    def stop(self, wait=False):
        '''sends SIGHUP to the process asking it to terminate'''
        self.signal('HUP')
        self._log("deferred wait not implemented", level='NOTIMPLEMENTED')
    # end stop

    def kill(self):
        '''sends a SIGKILL to the process informing it to terminate immediately'''
        self.signal('KILL')
    # end kill
# end Process


class ProcessFrame(Process):
    '''
    wraps Process and provides input based on a database
    entry
    '''

# end ProcessRow
