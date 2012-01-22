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

from __future__ import with_statement

import os
import psutil
import tempfile

from loghandler import log

# establish and create LOCK_ROOT if it does not exist
LOCK_ROOT = os.path.join(tempfile.gettempdir(), 'pyfarm', 'lock')
if not os.path.isdir(LOCK_ROOT):
    os.makedirs(LOCK_ROOT)

class ProcessLockError(Exception):
    '''raised when we had trouble acquiring a lock'''
    pass
# end ProcessLockError


class LockFile(object):
    '''stores, controls, and maintains a lockfile'''
    def __init__(self, name, pid):
        self.name = name
        self.pid = pid
        self.path = os.path.join(LOCK_ROOT, name)
    # end __init__

    def remove(self):
        '''removes the lock file on disk'''
        if os.path.isdir(self.path):
            os.remove(self.path)
            log.msg("removed lock file %s" % self.path)
    # end remove

    def locked(self):
        '''
        return True if the lock file exists on disk and has a valid process
        '''
        if not os.path.isfile(self.path):
            return False

        with open(self.path, 'r') as stream:
            data = stream.read()
            if not data.isdigit():
                return False

            try:
                process = psutil.Process(int(data))
                return process.is_running()

            except psutil.NoSuchProcess:
                log.msg("removing stale lock file %s" % stream.name)
                self.remove()
                return False
    # end locked

    def lock(self, force=False):
        '''
        attempts to lock the file

        :param boolean force:
            if True force overwrite the lock file

        :exception ProcessLockError:
            raised on failure to create the new lock file
        '''
        # If self.path already exists check to see if the
        # process id it contains is valid.  If is then raise
        # an exception otherwise remove the file
        if self.locked():
            if not force:
                msg = "'%s' is already running with pid %s" % (self.name, self.pid)
                raise ProcessLockError(msg)

            log.msg("force overwriting lock file %s!" % self.path)
            self.remove()

        with open(self.path, 'w') as lockfile:
            lockfile.write(str(self.pid))
            args = (self.name, self.pid)
            log.msg("wrote lock file for '%s' with pid %s" % args)
# end LockFile


class ProcessLock(object):
    '''
    provides a locking mechanism when provided a name and process id

    :exception :
    '''
    def __init__(self, name, pid, force=False):
        self.lock = LockFile(name, pid)
        self.lock.lock(force)
    # end __int__
# end ProcessLock


if __name__ == '__main__':
    import time

    lock = ProcessLock('test', os.getpid())
    time.sleep(3600)