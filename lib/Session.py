'''
HOMEPAGE: www.pyfarm.net
INITIAL: Dec 7 2010
PURPOSE: Store information such as process ID and other session
dependent information as a set of files

This file is part of PyFarm.
Copyright (C) 2008-2010 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys
import linecache

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import Logger, File, system

log = Logger.Logger(MODULE)

class State(object):
    '''
    Read and write state information about the current
    process.  Only information pretaining to process IDs will
    be included in this file.
    '''
    def __init__(self, context):
        self.context   = "%s.%s" % (context, system.Info.HOSTNAME)
        self.stateDir  = os.path.join(system.Info.PYFARMHOME, 'state')
        self.pidDir    = os.path.join(system.Info.PYFARMHOME, 'pid')
        self.pidFile   = os.path.join(self.pidDir, '%s.pid' % self.context)

        # create directories
        File.mkdir(self.stateDir)
        File.mkdir(self.pidDir)

    def write(self, force=False):
        '''Write the process ID to the directory'''
        if self.exists() and not force and not self.running():
            log.warning("PID file found for %s, user input required" % os.getpid())
            return False

        elif self.exists() and force:
            log.debug("Attempting to kill the process and remove the pid file")
            if not self.running():
                log.warning("Failed to find process, it must not be running")

            self._writePIDFile()

        else:
            log.info("Writing to PID %i File: %s" % (os.getpid(), self.pidFile))
            self._writePIDFile()

        return True

    def exists(self):
        '''
        Return true of the process id file exists
        '''
        if os.path.isfile(self.pidFile):
            return True
        else:
            return False

    def running(self):
        '''
        Checks to see if the process listed in the session file
        is currently running
        '''
        if self.exists():
            pid = open(self.pidFile, 'r').readlines()[0]
            log.debug("Got pid from file: %s" % pid)
            system.killProcess(pid)

        else:
            return False

    def _writePIDFile(self):
        '''Wrote the contents of the pid file'''
        pidFile = open(self.pidFile, 'w')
        pidFile.write(str(os.getpid()))
        pidFile.close()

        if self.exists():
            pid = open(self.pidFile, 'r').readlines()[0]
            log.debug("Success! Wrote pid %s to file" % pid)

    def close(self):
        '''Close out the state file(s) and remove them'''
        try:
            if self.exists():
                os.remove(self.pidFile)

        except:
            log.error("Failed to remove process state file!")