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
import os.path

from lib.Settings import ConfigLogger
from lib.system.Utility import backtrackDirs

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

        self.config = ConfigLogger(
                                            os.path.join(
                                                            backtrackDirs(__file__, 2),
                                                            "cfg",
                                                            "loglevels.xml"
                                                            )
                                            )
        from pprint import pprint
        pprint(self.config)

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
                15 : "FATAL",
                16 : "TERMINATED"
            }

        self.levelColors = {
                                "WARNING" : "\033[0;36m",
                                "CRITICAL" : "\033[1;33m",
                                "FATAL" : "\033[1;41m",
                                "TERMINATED" : "\033[0;32m"
                            }

        self.setName(name)
        self.setLevel(level)

        if logfile:
            self.logfile = open(logfile, "a")
        else:
            self.logfile = None

        # create log functions on load
        for level, name in self.levelList.items():
            vars(self)[name.lower()] = (lambda msg: self._out(name, msg))

    def _out(self, level, msg):
        '''Perform final formatting and output the message to the appropriate locations'''
        if level in self.levels:
            if level in self.levelColors.keys():
                out = "%s - %s%s%s - %s - %s" % (time.strftime(self.timeFormat),
                self.bold(1, level), level, self.bold(0, level),  self.name, msg)
            else:
                out = "%s - %s%-15s - %s - %s" % (time.strftime(self.timeFormat), '', level, self.name, msg)

            print out
            if self.logfile:
                self.logfile.write(out+"\n")
                self.logfile.flush()

    def bold(self, makeBold, error="\033[0m"):
        '''Return either bold or unbold strings'''
        if os.name == 'posix':
            if makeBold:
                if error in self.levelColors.keys():
                    return self.levelColors[error]
                else:
                    return error
            else:
                return "\033[0;0m"
        else:
            return ''

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

    def sqlite(self, msg): self._out(self.levelList[0], msg)
    def netpacket(self, msg): self._out(self.levelList[1], msg)
    def queue(self, msg): self._out(self.levelList[2], msg)
    def conditional(self, msg): self._out(self.levelList[3], msg)
    def netserver(self, msg): self._out(self.levelList[4], msg)
    def netclient(self, msg): self._out(self.levelList[5], msg)
    def network(self, msg): self._out(self.levelList[6], msg)
    def ui(self, msg): self._out(self.levelList[7], msg)
    def fixme(self, msg): self._out(self.levelList[8], msg)
    def notimplimented(self, msg): self._out(self.levelList[9], msg)
    def debug(self, msg): self._out(self.levelList[10], msg)
    def info(self, msg): self._out(self.levelList[11], msg)
    def warning(self, msg): self._out(self.levelList[12], msg)
    def error(self, msg): self._out(self.levelList[13], msg)
    def critical(self, msg): self._out(self.levelList[14], msg)

    def fatal(self, msg, exitCode=0):
        self._out(self.levelList[15], msg)
        sys.exit(exitCode)

    def terminate(self, msg):
        '''Terminate the running program with a final log message'''
        self._out(self.levelList[16], msg)
        sys.exit(0)
