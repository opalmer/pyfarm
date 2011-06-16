# No shebang line, this module is meant to be imported
#
# INITIAL: May 22 2010
# PURPOSE: To provide a standard logging facility for PyFarm
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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
import site
import string
import inspect
import xml.etree.ElementTree

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, ".."))
site.addsitedir(root)

XML_CONFIG = os.path.join(root, "cfg", "logger.xml")

def settings(path=XML_CONFIG):
    '''
    Read in an xml file and return a settings dictionary for use
    by the logger

    @param path: The path to the xml configuration file
    @type  path: C{str}
    '''
    out = {}
    try:
        if not os.path.isfile(path):
            raise IOError('%s is not a vaild file path' % path)

        dom = xml.etree.ElementTree.parse(path)
        root = dom.getroot()

        for child in root.getchildren():
            for element in child.getchildren():
                if not out.has_key(child.tag):
                    out[child.tag] = {}

                if element.tag == "attr":
                    # add global settings value to the dictionary
                    name = element.attrib['name']
                    value = element.attrib['value']

                    if name != 'template':
                        out[child.tag][name] = value
                    else:
                        out[child.tag][name] = string.Template(value)

                elif element.tag == "level":
                    # check and see if the function key exists, if it does
                    # not assume we are going to use the name for the function
                    # call
                    if not element.attrib.has_key('function'):
                        element.attrib['function'] = element.attrib['name']

                    # assume the level is enabled if we don't find the attribute
                    if not element.attrib.has_key('enabled'):
                        element.attrib['enabled'] = True
                    else:
                        element.attrib['enabled'] = eval(element.attrib['enabled'])

                    # assume solo is False if we don't find the attribute
                    if not element.attrib.has_key('solo'):
                        element.attrib['solo'] = False
                    else:
                        element.attrib['solo'] = eval(element.attrib['solo'])

                    # assign the level's dictionary to the full level
                    # dictionary
                    out['levels'][element.attrib['name']] = element.attrib

    except IOError:
        print "Could not read logger config, file does not exist: %s" % path

    except xml.parsers.expat.ExpatError:
        print "Failed to parse XML properly!"

    finally:
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
        self.name = name
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
        self.host = host
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
    @param level: The maximum level to output
    @type  level: C{int}
    @param log: File to log to
    @type  log: C{str}
    @param solo: If enabled, solo values will be respected for this logger
    @type  solo: C{bool}
    @param writeOnly: Write to disk only, do not print to stdout
    @type  writeOnly: C{bool}
    '''
    def __init__(self, name=None, level=None, log=None, writeOnly=False):
        self.writeOnly = writeOnly
        self.config = settings()
        self.log = log
        self.level = level or self.config['globals']['defaultLevel']

        # setting this property to false during execucution will
        # disable this logger
        self.disabled = False

        # override level and solo if they are defined above
        if eval(self.config['globals']['forceLevel']):
            self.level = int(self.config['globals']['defaultLevel'])

        self.levels = []
        self.levelCalls = []
        self.soloLevel = None
        self.setName(name)

        for function, data in self.config['levels'].items():
            solo = data['solo']
            name = data['name']
            enabled = data['enabled']
            function = data['function']
            level = self.newLevel(name, enabled, function)
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
        if level.name in self.levels and not self.disabled:
            cfg = self.config['levels'][level.name]
            enabled = cfg['enabled']
            solo = cfg['solo']
            enabledSolo = solo and enabled
            soloLevel = self.soloLevel and enabled

            # stack inspection
            stack = inspect.stack()[2]
            frame = stack[0]
            split = stack[1].split(os.sep)

            if len(split) > 1:
                head = split[-2]
            else:
                head = split[-1]

            tail = split[-1].split(".")[0]
            module = '.'.join((head, tail))

            if head == '.':
                module = tail

            trace = "%s:%i" % (module, frame.f_lineno)

            if enabledSolo or not soloLevel or solo and not enabled:
                out = (
                       self.config['globals']['template'].substitute(
                         time=time.strftime(self.config['globals']['timeFormat']),
                         logger="%15s" % trace,
                         level="%15s"  % level.name.upper(),
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
