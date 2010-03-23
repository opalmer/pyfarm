'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 22 2010
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

import logging

class LogLevelException(Exception):
    '''
    Raised when an invalid level is presented to the
    logger by the user
    '''
    def __init__(self, level):
        self.level = level

    def __str__(self):
        return repr("%s is not a valid log level" % self.level)


class LogMain(logging):
    '''Main logging object used to configure other loggers'''
    def __init__(self, module="PyFarm", level="INFO", log="PyFarm.log"):
        self.levels = {
                        "DEBUG" : self.DEBUG,
                        "INFO" : self.INFO,
                        "WARNING" : self.WARNING,
                        "ERROR" : self.ERROR,
                        "CRITICAL" : self.CRITICAL
                      }

        if maxLevel.upper() not in self.levels.keys():
            raise LogLevelException(maxLevel)
        else:
            self.setLevel(self.levels[maxLevel.upper()])

        self.log = open(logFile, "w+")
        self.module = module

    def _writeToLog(self, line):
        '''Write the given line to the given file'''
        self.log.write(line)
        self.log.flush()

    # reimpliment the logger's methods
    def debug(self, line): pass
    def info(self, line): pass
    def warning(self, line): pass
    def error(self, line): pass
    def critical(self, line): pass


class NetLog(LogMain):
    def __init__(self):
        super(NetLog, self).__init__()