'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 22 2010
PURPOSE: To provide a standard logging facility for PyFarm

This file is part of PyFarm.
Copyright (C) 2008-2010 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

import sys
import time

__LOGLEVEL__ = 4
__GLOBAL_LOGLEVEL__ = 0 # set to None to disable
__MODULE__ = "lib.Logger"

class Logger(object):
    '''
    Custom logging object for PyFarm

    VARS:
        level (int) -- minimum level to log
        solo (bool) -- If set the true only requests matching level will be served
        logfile (str) -- file to log to
    '''
    def __init__(self, name, level=5, logfile=None, solo=False):
        if __GLOBAL_LOGLEVEL__ != None:
            self.level = __GLOBAL_LOGLEVEL__
            self.override = 1
        else:
            self.level = level
            self.override = 0

        self.solo = solo
        self.timeFormat = "%Y-%m-%d %H:%M:%S"

        self.levelList = {
                0 : "SQLITE",
                1 : "NETPACKET",
                2 : "QUEUE",
                3 : "CONDITIONAL",
                4 : "NETWORK.SERVER",
                5 : "NETWORK.CLIENT",
                6 : "NETWORK",
                7 : "UI",
                8 : "FIXME",
                9 : "NOT IMPLIMENTED",
                10 : "DEBUG",
                11 : "INFO",
                12 : "WARNING",
                13 : "ERROR",
                14 : "CRITICAL",
                15 : "FATAL"
            }

        self.setName(name)
        self.setLevel(level)

        if logfile:
            self.logfile = open(logfile, "a")
        else:
            self.logfile = None

    def _out(self, level, msg):
        '''Perform final formatting and output the message to the appropriate locations'''
        out = "%s - %s - %s - %s" % (time.strftime(self.timeFormat), level, self.name, msg)
        if level in self.levels:
            print out
            if self.logfile:
                self.logfile.write(out+"\n")
                self.logfile.flush()

    def close(self):
        '''Close out the log file'''
        self.logfile.close()

    def setName(self, name):
        '''Set the name for the logger'''
        self.name = name

    def setLevel(self, level):
        '''Set the level and configure the level list'''
        if __GLOBAL_LOGLEVEL__ != None and not self.solo:
            levelKeys = self.levelList.keys()[__GLOBAL_LOGLEVEL__:]
            self.levels = [ self.levelList[level] for level in levelKeys ]
        elif self.solo and type(solo) == str:
            print "SELF.SOLO STRING NOT IMPLIMENTED!"
        elif self.solo and type(solo) == list:
            print "SELF.SOLO LIST NOT IMPLIMENTED!"
        else:
            self.levels = self.levelList[self.level]

    def setSolo(self, solo):
        '''If set to 1 only the logLevel matching solo will be output'''
        self.solo = solo
        self.levels = self.levelList[self.solo]

    def sqlite(self, msg):
        '''Print a sqlite message'''
        self._out(self.levelList[0], msg)

    def netpacket(self, msg):
        '''Print a netpacket message'''
        self._out(self.levelList[1], msg)

    def queue(self, msg):
        '''Print a queue message'''
        self._out(self.levelList[2], msg)

    def conditional(self, msg):
        '''Print a conditional message'''
        self._out(self.levelList[3], msg)

    def netserver(self, msg):
        '''Print a network server message'''
        self._out(self.levelList[4], msg)

    def netclient(self, msg):
        '''Print a network client message'''
        self._out(self.levelList[5], msg)

    def network(self, msg):
        '''Print a general network message'''
        self._out(self.levelList[6], msg)

    def ui(self, msg):
        '''Print a ui message'''
        self._out(self.levelList[7], msg)

    def fixme(self, msg):
        '''Print a Fix Me message'''
        self._out(self.levelList[8], msg)

    def notimplimented(self, msg):
        '''Return a Not Implimented message'''
        self._out(self.levelList[9], msg)

    def debug(self, msg):
        '''Print a debug message'''
        self._out(self.levelList[10], msg)

    def info(self, msg):
        '''Print an info message'''
        self._out(self.levelList[11], msg)

    def warning(self, msg):
        '''Print a warning message'''
        self._out(self.levelList[12], msg)

    def error(self, msg):
        '''Print an error message'''
        self._out(self.levelList[13], msg)

    def critical(self, msg):
        '''Print a critical message'''
        self._out(self.levelList[14], msg)

    def fatal(self, msg):
        '''Print a fatal error message and exit'''
        self._out(self.levelList[15], msg)
        sys.exit(1)