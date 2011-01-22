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

# default values for all loggers
DEFAULT_LEVEL   = 4
DEFAULT_SOLO    = False

# overrides and global settings
GLOBAL_LEVEL    = False
GLOBAL_OVERRIDE = False
GLOBAL_SOLO     = False
XML_CONFIG      = os.path.join(PYFARM, "cfg", "logger.xml")

def settings(path):
    '''
    Load and return settings information from the given xml file

    @param path: full path to the xml file
    @type  path: C{str}s
    '''
    if not os.path.isfile(path):
        raise IOError('%s is not a vaild file path' % path)

    level   = 0
    out     = {}
    xml     = minidom.parse(path)
    globals = out['globals'] = {}
    levels  = out['levels']  = {}

    for attrib in xml.getElementsByTagName("attr"):
        globals['name']  = str(attrib.getAttribute("name"))
        globals['value'] = str(attrib.getAttribute("value"))

    for element in xml.getElementsByTagName("level"):
        name    = str(element.getAttribute("name"))
        enabled = eval(element.getAttribute("enabled"))
        solo    = eval(element.getAttribute("solo"))

        # retrieve function name
        if element.hasAttribute("function"):
            function = str(element.getAttribute("function"))
        else:
            function = name

        # place element into output dictionary
        levels[name] = {
                           'level'    : level,
                           'name'     : name,
                           'function' : function,
                           'solo'     : solo,
                           'enabled'  : enabled,
                           'template' : string.Template(
                           '$time - $logger - %s - $message' % name.upper()
                           )
                      }
        level += 1

    return out


class LevelName(object):
    '''
    Level name object that controls and configures the final output of a logger
    as well as producing custom attributes to be used for processing.

    @param name: name of the log level to be output
    @type  name: C{str}
    @param enabled: controls if this log level is allowed to output
    @type  enabled: C{bool}
    '''
    def __init__(self, name, enabled):
        self.name    = name
        self.enabled = enabled


class Level(object):
    '''
    Individual level object to hold information pretaining to output
    of a log line.

    @param method: method being called during output
    @type  method: Logger_out
    @param host: LevelName containing minor pieces of information
    @type  host: LevelName
    @param method_name: the name of the function to be called when a level
                        is called
    @type  method_name: C{str}
    '''
    def __init__(self, method, host, method_name=None):
        self.host   = host
        self.method = method
        setattr(host, method_name or method.__name__, self)

    def __call__(self, *args, **kwargs):
        nargs = [self.host]
        nargs.extend(args)
        return apply(self.method, nargs, kwargs)


class Logger(object):
    '''
    Custom logging object for PyFarm.  Includes various options to control
    the output and behavior of the class.

    @param name: Name of logger to create
    @type  name: C{str}
    @param solo: If enabled, solo values will be respected for this logger
    @type  solo: C{bool}
    @param log: File to log to
    @type  log: C{str}
    @param writeOnly: Write to disk only, do not print to stdout
    @type  writeOnly: C{bool}
    '''
    def __init__(self, name, level=DEFAULT_LEVEL, log=None, solo=DEFAULT_SOLO, writeOnly=False):
        self.level      = level
        self.solo       = solo
        self.writeOnly  = writeOnly
        self.config     = settings(XML_CONFIG)
        self.timeFormat = "%Y-%m-%d %H:%M:%S"
        self.log        = log

        # override level and solo if they are defined above
        if GLOBAL_LEVEL:
            self.level = DEFAULT_LEVEL

        if GLOBAL_SOLO:
            self.solo  = DEFAULT_SOLO

        self.levels     = []
        self.levelCalls = []
        self.soloLevel  = None
        self.setName(name)

        for function, data in self.config['levels'].items():
            solo     = data['solo']
            name     = data['name']
            enabled  = data['enabled']
            function = data['function']
            level    = self.newLevel(name, enabled, function)
            vars(self)[function] = level

            if solo and not self.soloLevel:
                self.soloLevel = name

            # append info to lists
            self.levels.append(name)
            self.levelCalls.append(function)

        if self.log:
            self.log = open(log, "a")

        else:
            self.log = None

        if writeOnly and not self.log:
            raise IOError("You declared writeOnly without a logfile")

    def newLevel(self, name, enabled, function):
        '''
        Create a new log level

        @param name: name of the log level to be output
        @type  name: C{str}
        @param enabled: controls if this log level is allowed to output
        @type  enabled: C{bool}
        @param function: name of function to pass to Level object
        @type  function: C{str}
        '''
        return Level(self._out, LevelName(name, enabled), function)

    def _out(self, level, msg):
        '''
        Evalulate input arguments and settings, output the appropriate
        locations

        @param level: The requested level to output
        @type  level: Logger.Level
        @param msg: the message to print
        @type  msg: C{str}
        '''
        if level.name in self.levels:
            cfg         = self.config[level.name]
            enabled     = cfg['enabled']
            solo        = cfg['solo']
            enabledSolo = solo and enabled
            soloLevel   = self.soloLevel and enabled

            if enabledSolo or not soloLevel or solo and not enabled:
                template = cfg['template']
                out      = (
                            template.substitute(
                              time=time.strftime(self.timeFormat),
                              logger=self.name,
                              message=msg
                            )
                           )

                print out

                if self.log:
                    self.log.write(out+os.linesep)
                    self.log.flush()

    def setName(self, name):
        '''
        Set the overall name for the Logger object

        @param name: name to call the logger by
        @type  name: C{str}
        '''
        self.name = name

    def close(self):
        '''Close out the log file'''
        self.log.close()

if __name__ == '__main__':
    log = Logger('test')
    log.debug('test')
