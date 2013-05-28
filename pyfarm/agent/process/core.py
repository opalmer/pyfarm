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
import pwd
import datetime
from UserDict import UserDict

from twisted.internet import task, reactor, error

from pyfarm import errors
from pyfarm.agent.process.protocol import ProcessProtocol
from pyfarm.agent.process.stats import MemorySurvey
from pyfarm.datatypes.enums import OperatingSystem, State
from pyfarm.datatypes.system import USER, OS
from pyfarm.db import tables
from pyfarm.logger import Logger

# TODO: use pyfarm.config
# TODO: remove database calls


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
