'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 22 2010
PURPOSE: To provide a standard logging facility for PyFarm

    This file is part of PyFarm.
    Copyright (C) 2008-2011 Oliver Palmer

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
import time
import string
from xml.dom import minidom

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import Settings

# default values for all loggers
DEFAULT_LEVEL   = 4
DEFAULT_SOLO    = False

# overrides and global settings
GLOBAL_LEVEL    = False
GLOBAL_OVERRIDE = False
GLOBAL_SOLO     = False
XML_CONFIG      = os.path.join(PYFARM, "cfg", "loglevels.xml")

class LevelName(object):
    def __init__(self, name, enabled):
        self.name    = name
        self.enabled = eval(enabled)


class Level(object):
    def __init__(self, method, host, method_name=None):
        self.host = host
        self.method = method
        setattr(host, method_name or method.__name__, self)

    def __call__(self, *args, **kwargs):
        nargs = [self.host]
        nargs.extend(args)
        return apply(self.method, nargs, kwargs)

class Logger(object):
    '''
    Custom logging object for PyFarm

    VARS:
        level (int) -- minimum level to log
        enableSolo (bool) -- If enabed, solo values will be respected for
        this logger
        logfile (str) -- file to log to
    '''
    def __init__(self, name, level=DEFAULT_LEVEL, logfile=None, enableSolo=DEFAULT_SOLO, writeOnly=False):
        self.enableSolo = enableSolo
        self.level      = level
        self.config     = Settings.ReadConfig.logger(XML_CONFIG)

        # check for global overrides
        if GLOBAL_LEVEL: self.level = DEFAULT_LEVEL
        if GLOBAL_SOLO:  self.solo  = DEFAULT_SOLO

        self.timeFormat = "%Y-%m-%d %H:%M:%S"
        self.setName(name)

        self.levels = []
        for function, levelDict in self.config.items():
            newLevel = self.newLevel(levelDict['name'], levelDict['enabled'], function)
            vars(self)[levelDict['function']] = newLevel
            self.levels.append(levelDict['name'])

        if logfile:
            self.logfile = open(logfile, "a")
        else:
            self.logfile = None

        self.writeOnly = writeOnly
        if writeOnly and not logfile:
            raise RuntimeError("You declare writeOnly without a logfile")

    def newLevel(self, name, enabled, function):
        '''Create a new log level'''
        return Level(self._out, LevelName(name, enabled), function)

    def _out(self, level, msg):
        if level.name in self.levels:
            cfg = self.config[level.name]

            if not self.writeOnly and level.enabled:
                print (cfg['template'].substitute(
                    time=time.strftime(self.timeFormat),
                    logger=self.name,
                    message=msg
                ))

            if self.logfile:
                self.logfile.write(out+os.linesep)
                self.logfile.flush()

    def setSolo(self, solo):
        '''If set to 1 only the logLevel matching solo will be output'''
        self.solo = solo
        self.levels = self.levelList[self.solo]

    def close(self):
        '''Close out the log file'''
        self.logfile.close()

    def setName(self, name):
        '''Set the name for the logger'''
        self.name = name
