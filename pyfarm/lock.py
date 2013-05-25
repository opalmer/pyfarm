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
import time
import atexit
import tempfile

import psutil

from pyfarm.logger import Logger


KILL_SLEEP = 2
LOCK_ROOT = os.path.join(tempfile.gettempdir(), 'pyfarm', 'lock')
logger = Logger(__name__)

# create root lock folder if it does not exist
if not os.path.isdir(LOCK_ROOT):
    os.makedirs(LOCK_ROOT)
    logger.debug("created directory: %s" % LOCK_ROOT)

class ProcessLockError(Exception):
    """raised when we had trouble acquiring a lock"""
    def __init__(self, msg=None):
        super(ProcessLockError, self).__init__(msg)
    # end __init__
# end ProcessLockError


class LockFile(Logger):
    """stores, controls, and maintains a lockfile"""
    def __init__(self, name, pid):
        Logger.__init__(self, self)
        self.name = name
        self.pid = pid
        self.path = os.path.join(LOCK_ROOT, name)

        pid = self.filepid()
        if pid is not None and not psutil.pid_exists(pid):
            self.warning("removing stale lock file")
            self.remove()
    # end __init__

    def remove(self):
        """removes the lock file on disk"""
        if os.path.isfile(self.path):
            os.remove(self.path)
            self.debug("removed lock file %s" % self.path)
    # end remove

    def filepid(self):
        """returns the pid in the file or None"""
        if not os.path.isfile(self.path):
            return None

        with open(self.path, 'r') as stream:
            return int(stream.read())
    # end filepid

    def locked(self):
        """
        return True if the lock file exists on disk and has a valid process
        """
        if not os.path.isfile(self.path):
            return False

        with open(self.path, 'r') as stream:
            data = stream.read()

            # return False if the data in the file
            # cannot be converted to a number
            if not data.isdigit():
                return False

            data = int(data)

            if not psutil.pid_exists(data):
                self.remove()
                return False

            return True
    # end locked

    def lock(self, force=False):
        """
        attempts to lock the file

        :param boolean force:
            if True force overwrite the lock file

        :exception ProcessLockError:
            raised on failure to create the new lock file
        """
        # check to see if the lock file on disk is stale and
        # if it is, remove it
        filepid = self.filepid()
        if isinstance(filepid, int) and not psutil.pid_exists(filepid):
            self.warning("process id in lock file is stale and will be removed")
            self.remove()

        # If self.path already exists check to see if the
        # process id it contains is valid.  If is then raise
        # an exception otherwise remove the file
        if self.locked():
            if not force:
                pid = self.filepid()
                msg = "'%s' is already running with pid %s" % (self.name, pid)
                raise ProcessLockError(msg)

            self.warning("force overwriting lock file %s!" % self.path)
            self.remove()

        with open(self.path, 'w') as lockfile:
            lockfile.write(str(self.pid))
            args = (self.name, self.pid)
            self.debug("wrote lock file for '%s' with pid %s" % args)
    # end lock
# end LockFile


class ProcessLock(object, Logger):
    """
    provides a locking mechanism when provided a name and process id

    :param boolean wait:
        blocks unlock the current lock releases

    :param boolean force:
        forces a process to unlock on creation of the ProcessLock

    :param boolean kill:
        kills any currently running process before attempting to acquire
        a lock

    :param boolean register:
        registers an exit handler which will remove the lock file
        with Python exits

    :param boolean remove:
        removes the lock file on disk if one exists

    :exception ProcessLockError:
        raised if we failed to acquire a lock
    """
    def __init__(self, name, pid=None, force=False, wait=False, kill=False,
                 register=True, remove=False):
        Logger.__init__(self, self)
        self.name = name
        self.pid = pid or os.getpid()
        self.lock = LockFile(name, self.pid)
        self.actions = []

        if remove:
            self.lock.remove()

        if kill and self.lock.locked():
            pid = self.lock.filepid()
            process = psutil.Process(pid)
            process.kill()
            self.info("killed %s, pausing for %i seconds" % (pid, KILL_SLEEP))
            time.sleep(KILL_SLEEP)

        # wait for process to finish if wait is True
        if wait and self.lock.locked():
            filepid = self.lock.filepid()
            self.info("waiting for process %s to release the lock" % filepid)
            while self.lock.locked():
                time.sleep(1)

        self.lock.lock(force)

        if register:
            atexit.register(self.lock.remove)
    # end __int__

    def __enter__(self):
        # if the lock.remove function has been registered as an exit
        # handler then we should remove it if we are using the context
        # manager on ProcessLock
        for function, args, kwargs in atexit._exithandlers:
            # only remove exit handlers that match this
            # ProcessLock's self.lock.remove method
            if function == self.lock.remove:
                atexit._exithandlers.remove((function, args, kwargs))
                self.debug("removed exit handler ProcessLock(%s)" % self.name)

        return self
    # end __enter__

    def __exit__(self, type, value, trackback):
        # run all exit actions
        for action, args, kwargs in self.actions:
            action(*args, **kwargs)

        if self.lock.locked():
            self.lock.remove()
    # end __exit__

    def addExitAction(self, method, args=(), kwargs={}):
        """adds a method to be called on exit"""
        self.actions.append((method, args, kwargs))
        self.debug("added exit action - %s" % method.func_name)
    # end addExitAction
# end ProcessLock
