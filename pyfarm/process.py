# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

import os
import datetime
from UserDict import UserDict

import psutil
from twisted.internet import task


try:
    import pwd

except ImportError:
    pwd = None

from pyfarm.logger import Logger, Observer
from pyfarm.jobtypes.base import job
from pyfarm.datatypes.objects import ScheduledRun
from pyfarm.datatypes.enums import State, OperatingSystem
from pyfarm.datatypes.system import OS, USER
from pyfarm.db import tables
from pyfarm import errors

# TODO: add pyfarm.config code

from twisted.internet import protocol, reactor, error

# TODO: documentation
# TODO: db handling on process success/finish

class MemorySurvey(ScheduledRun, Logger):
    def __init__(self):
        ScheduledRun.__init__(self, prefs.get('host.proc-mem-survey-interval'))
        Logger.__init__(self, self)
        self.process = None
        self.pid = None
        self.vms = []
        self.rss = []
    # end __init__

    def setup(self, pid):
        if self.process is None:
            self.process = psutil.Process(pid)
            self.pid = pid
    # end process

    def run(self, force=False):
        if self.process is None:
            self.error("process has not been setup yet")

        elif self.shouldRun(force):
            meminfo = self.process.get_memory_info()
            vms = [ meminfo.vms ]
            rss = [ meminfo.rss ]

            for process in self.process.get_children(recursive=True):
                child_meminfo = process.get_memory_info()
                vms.append(child_meminfo.vms)
                rss.append(child_meminfo.rss)

            # get the total ram usage across all processes (children
            # included)
            vms = sum(vms) / 1024 / 1024
            rss = sum(rss) / 1024 / 1024

            # since could be
            if vms not in self.vms:
                self.vms.append(vms)

            if rss not in self.rss:
                self.rss.append(rss)

            args = (self.pid, vms, rss)
            self.debug("ran memory survey for parent %s (vms: %s, rss: %s)" % args)
    # end run
# end MemorySurvey


class ProcessProtocol(protocol.ProcessProtocol, Logger):
    def __init__(self, process, arguments, log):
        Logger.__init__(self, self)
        self.process = process
        self.arguments = arguments

        # setup the logger
        self.observer = Observer(log)
        self.process_logger = Logger(stdout_observer=False, inherit_observers=False)
        self.addObserver(self.observer)
        self.process_logger.addObserver(self.observer)
    # end __init__

    def connectionMade(self):
        """send a log message the the process log file"""
        self.transport.write(" ".join(self.arguments))
        self.transport.closeStdin()
        self.process.running = True
    # end connectionMade

    def outReceived(self, data):
        self.process_logger.msg(data.strip(), level='STDOUT', system=None)
    # end outReceived

    def errReceived(self, data):
        self.process_logger.msg(data.strip(), level='STDERR', system=None)
    # end errReceived

    def processExited(self, reason):
        self.process.stopped(reason)
        self.removeObserver(self.observer)
    # end processExited
# end ProcessProtocol


class Process(Logger):
    """
    wraps the process protocol and separates the start, stop, and signaling
    from the  process protocol itself
    """
    def __init__(self, command, args, environ, log, user=None, job=None):
        Logger.__init__(self, self)
        self._command = command
        self._args = args
        self.job = job
        self.user = user or USER
        self.process = None
        self.pid = None
        self.runnning = False
        self.environ = {}
        self.command = " ".join(args)
        self.protocol = ProcessProtocol(self, self._args, log)
        self.memsurvey = MemorySurvey()
        self.memsurvey_task = task.LoopingCall(self.memsurvey.run)

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

    def updateState(self, state):
        state_name = State.get(state)

        if self.job is None:
            self.error("cannot update database, job is None")
            return

        elif state not in (State.FAILED, State.DONE, State.RUNNING):
            msg = "not updating state to %s in db, can only" % state_name
            msg += "update to DONE, FAILED, or RUNNING"
            self.warning(msg)
            return

        with Session(tables.frames) as trans:
            frame = trans.query.filter(
                tables.frames.c.id == self.job.row_frame.id
            ).first()

            if frame is None:
                raise errors.FrameNotFound(id=self.job.row_frame.id)

            args = (State.get(self.job.row_frame.state), state_name)
            self.info("updating frame state from %s to %s" % args)
            frame.state = state

            if state == State.RUNNING:
                start = datetime.datetime.now()
                self.info("updating frame start to %s" % start)
                frame.time_start = start
                frame.time_end = None

                args = (frame.attempts, frame.attempts + 1)
                self.info("updating frame attempts from %s to %s" % args)
                frame.attempts += 1
            else:
                end = datetime.datetime.now()
                frame.time_end = end
                self.info("updating frame end to %s" % end)

            raise NotImplementedError("!!! TODO :hostid should be set by master")

            if state in (State.DONE, State.FAILED):
                ramuse = max(self.memsurvey.rss)
                self.info("updating frame ram usage to %s" % ramuse)
                frame.ram = ramuse
    # end updateState

    def start(self):
        if self.process is None:
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

            if self.running:
                self.started()

        else:
            self.warning("process already started (pid %s)" % self.pid)
    # end start

    def started(self):
        """called when the process has started"""
        self.pid = self.process.pid
        self.info("process %s started" % self.pid)

        # update the database with our host's current ram usage
        update_memory.update(force=True, reset=False)

        # update the row to indicate this frame is running (and
        # on what host)
        self.updateState(State.RUNNING)

        # start the memory survey task
        self.memsurvey.setup(self.pid)
        self.memsurvey_task.start(self.memsurvey.timeout)
    # end started

    def stopped(self, reason):
        """called when the process has stopped"""
        # turn off the memory survey
        self.memsurvey.process = None
        self.memsurvey_task.stop()
        self.process = None

        exit_code = reason.value.exitCode
        info = "process %s exited with status %s" % (self.pid, exit_code)
        self.info(info)

        if exit_code != 0:
            if isinstance(exit_code, int):
                args = (self.process.command, exit_code, self.pid)
                error = "%s failed (exit %s, pid %s)" % args
                self.error(error)

            elif exit_code is None:
                self.error("%s failed to return an exit code" % self.pid)

            self.updateState(State.ASSIGN)
            raise NotImplementedError(
                "!!! TODO: rerun failed frames locally (not via master)"
            )
        else:
            self.updateState(State.DONE)

        update_memory.update(force=True, reset=False)
    # end stopped

    def signal(self, signal):
        try:
            self.warning("sending SIG%s signal to %s" % (signal, self.pid))
            self.process.signalProcess(signal)

        except error.ProcessExitedAlready:
            self.warning("process %s has already stopped" % self.pid)
    # end signal

    def stop(self, wait=False):
        """sends SIGHUP to the process asking it to terminate"""
        self.signal('HUP')
    # end stop

    def kill(self):
        """sends a SIGKILL to the process informing it to terminate immediately"""
        self.signal('KILL')
    # end kill
# end Process


class ProcessFrame(job.Frame, Process):
    """
    Wraps Process when provided input based on the frame id.  This class
    should be inherited by any job class looking to setup and
    run a command.  See the methods on the Process class to determine
    prerun, postrun, and exit behaviors.
    """
    def __init__(self, id):
        job.Frame.__init__(self, id)

        Process.__init__(self,
            self.command, self.args,
            self.environ, self.logfile,
            user=self.user, job=self
        )
    # end __init__
# end ProcessRow
