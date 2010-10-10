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
import os
import sys
import time
import string
from xml.dom import minidom

from lib.system.Utility import backtrackDirs

LOGLEVEL = 4
GLOBAL_LOGLEVEL = 0 # set to None to disable
MODULE = "lib.Logger"

class LevelName(object):
    def __init__(self, name):
        self.name = name


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
        solo (bool) -- If set the true only requests matching level will be served
        logfile (str) -- file to log to
    '''
    def __init__(self, name, level=5, logfile=None, solo=False, writeOnly=False):
        if __GLOBAL_LOGLEVEL__ != None:
            self.level = __GLOBAL_LOGLEVEL__
            self.override = 1
        else:
            self.level = level
            self.override = 0

        self.xml = os.path.join(
                                    backtrackDirs(__file__, 2),
                                    "cfg",
                                    "loglevels.xml"
                                )

        self.config = self.readConfig(self.xml)
        self.solo = solo
        self.timeFormat = "%Y-%m-%d %H:%M:%S"
        self.setName(name)

        self.levels = []
        for function, levelDict in self.config.items():
            vars(self)[levelDict['function']] = self.newLevel(levelDict['name'], function)
            if levelDict['enabled']:
                self.levels.append(levelDict['name'])

        if logfile:
            self.logfile = open(logfile, "a")
        else:
            self.logfile = None

        self.writeOnly = writeOnly
        if writeOnly and not logfile:
            raise RuntimeError("You declare writeOnly without a logfile")

    def newLevel(self, name,  function):
        '''Create a new log level'''
        return Level(self._out, LevelName(name), function)

    def _out(self, level, msg):
        if level.name in self.levels:
            cfg = self.config[level.name]

            if not self.writeOnly:
                print cfg['template'].substitute(
                    time=time.strftime(self.timeFormat),
                    logger=self.name,
                    message=msg
                )

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

    def readConfig(self, xml):
        '''Read the logging configuration xml file and return a dictionary'''
        out = {}
        xml = minidom.parse(xml)

        for element in xml.getElementsByTagName("level"):
            level   = int(element.getAttribute("value"))
            name    = str(element.getAttribute("name"))
            enabled = str(element.getAttribute("enabled"))

            # function name
            if element.hasAttribute("function"):
                function = str(element.getAttribute("function"))
            else:
                function = name

            # terminal color (linux only)
            if element.hasAttribute("color") and os.name == 'posix':
                coloron  = str(element.getAttribute("color"))
                coloroff = str(element.getAttribute("color")) + 'OFF'
            else:
                coloron = ''
                coloroff = ''

            # bold attribute
            if element.hasAttribute("bold") and os.name == 'posix':
                boldon  = 'BOLD' # this should really be getting the bold VALUE
                boldoff = 'UNBOLD'
            else:
                boldon  = ''
                boldoff = ''

            # place element into output dictionary
            out[name] = {
                            'level'    : level,
                            'name'     : name,
                            'function' : function,
                            'coloron'  : coloron,
                            'coloroff' : coloroff,
                            'boldon'   : boldon,
                            'boldoff'  : boldoff,
                            'enabled'  : enabled,
                            'template' : string.Template(
                                         '%s$time - $logger - %s%s%s - $message%s' % (
                                                    coloron,  boldon,
                                                    name.upper(), boldoff,
                                                    coloroff)
                                                )
                         }
        return out