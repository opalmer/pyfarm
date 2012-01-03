# No shebang line, this module is meant to be imported
#
# INITIAL: Dec 7 2010
# PURPOSE: Store information such as process ID and other session
#          dependent information as a set of files
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
import sys
import time
from threading import Thread

from PyQt4 import QtCore

import logger
import fileSystem
import system

logger = logger.Logger()

class AcquisitionThread(QtCore.QThread):
    def __init__(self, semaphore, parent=None):
        super(AcquisitionThread, self).__init__(parent)
        self.ACQUIRED = False
        self.semaphore = semaphore

def run(semaphore):
    '''Attempt to acquire a lock on the semaphore'''
    print "hereA"
    thread = Thread(target=semaphore.acquire)
    thread.run()
    print "here"
    return True


class SystemSemaphore(QtCore.QSystemSemaphore):
    '''System level semaphore locking system with timeouts'''
    def _tryAcquire(self, timeout):
        '''Attempt to acquire a lock inside of a thread'''
        start = time.time()
        maxTime = start+timeout
        #thread = Thread(target=run(self))
        #thread.start()

        while time.time() <= maxTime and not self.acquire():
            print 'hi'
        print 'timeout'

        #if thread.ACQUIRED:
            #print 'acquired lock'
            #return True
        #else:
            #print 'could not acquire lock'
            #return False

    def tryAcquire(self, timeout=5):
        if not self._tryAcquire(timeout):
            return False
        return True


class State(object):
    '''
    Read and write state information about the current
    process.  Only information pretaining to process IDs will
    be included in this file.
    '''
    def __init__(self, context):
        self.context = "%s.%s" % (context, system.info.HOSTNAME)
        self.stateDir = os.path.join(system.info.PYFARMHOME, 'state')
        self.pidDir = os.path.join(system.info.PYFARMHOME, 'pid')
        self.pidFile = os.path.join(self.pidDir, '%s.pid' % self.context)

        # create directories
        fileSystem.mkdir(self.stateDir)
        fileSystem.mkdir(self.pidDir)

    def _writePIDFile(self):
        '''Wrote the contents of the pid file'''
        pidFile = open(self.pidFile, 'w')
        pidFile.write(str(self.pid(live=True)))
        pidFile.close()

        if self.exists():
            pid = self.pid()
            logger.debug("Success! Wrote pid %s to file" % pid)

    def running(self):
        '''
        Checks to see if the process listed in the session file
        is currently running
        '''
        if self.exists():
            pid = self.pid()
            if system.process.exists(pid):
                return True

        else:
            return False

    def write(self, force=False):
        '''Write the process ID to the directory'''
        if self.exists() and not force and self.running():
            logger.warning("PID file found for %s, user input required" % self.pid(live=True))

        elif self.exists() and force:
            logger.debug("Attempting to kill the process and remove the pid file")
            self.kill()
            self._writePIDFile()

        else:
            logger.info("Writing to PID %i File: %s" % (self.pid(live=True), self.pidFile))
            self._writePIDFile()

    def exists(self):
        '''Return true of the process id file exists'''
        if os.path.isfile(self.pidFile):
            return True
        else:
            logger.warning("PID file does not exist")
            return False

    def kill(self):
        '''Kill the currently running process'''
        if self.running():
            system.process.kill(self.pid())

        else:
            logger.warning("Cannot kill a process that is not running")

        self.remove()

    def pid(self, live=False):
        '''Get and return the process id'''
        if not live:
            return int(open(self.pidFile, 'r').readlines()[0])
        else:
            return os.getpid()

    def remove(self):
        '''Remove the default pid file'''
        if self.exists():
            os.remove(self.pidFile)

    def close(self):
        '''Close out the state file(s) and remove them'''
        try:
           self.remove()

        except:
            logger.error("Failed to remove process state file!")
