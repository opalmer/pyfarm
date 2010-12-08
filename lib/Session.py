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

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import Logger, File, system

log = Logger.Logger(MODULE)

class StateFile(object):
    '''
    StateFile object to hold the information necessary to locate
    and read an individual state fileChanged
    '''
    def __init__(self, filepath, context):
        self.path    = filepath
        self.context = context
        self.exists  = False

        if os.path.isfile(filepath):
            self.exists = True

        self.pid     = self.getpid()

    def update(self):
        '''Update the state file variables'''
        self.__init__(self.path, self.context)

    def getpid(self):
        '''Set the pid'''
        if self.exists:
            return int(open(self.path, 'r').readlines()[0])


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
        self.stateFile = StateFile(self.pidFile, self.context)

        # create directories
        File.mkdir(self.stateDir)
        File.mkdir(self.pidDir)

    def writePID(self, force=False):
        '''Write the process ID to the directory'''
        if os.path.isfile(self.pidFile) and not force:
            log.warning("PID file found for %s, user input required" % os.getpid())
            return False

        elif os.path.isfile(self.pidFile) and force:
            log.debug("Attempting to kill the process and remove the pid file")
            if not self.pidExists(): pass # log warning of missing process
            self._writePIDFile()
            self.stateFile.update()

        else:
            log.info("Writing to PID %i File: %s" % (os.getpid(), self.pidFile))
            self._writePIDFile()
            self.stateFile.update()

        return True

    def pidExists(self):
        '''Checks to see if the process list in the file is actually running'''
        pidFile = open(self.pidFile, 'r')
        lines   = pidFile.readlines()
        pidFile.close()

        pid = int(lines[0])
        system.killProcess(pid)

    def _writePIDFile(self):
        '''Wrote the contents of the pid file'''
        pidFile = open(self.pidFile, 'w')
        pidFile.write(str(os.getpid()))
        pidFile.close()

    def close(self):
        '''Close out the state file(s) and remove them'''
        try:
            os.remove(self.stateFile.path)
        except:
            pass