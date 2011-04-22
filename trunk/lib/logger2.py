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
import string
import inspect
import UserList
import xml.etree.ElementTree

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

XML_CONFIG = os.path.join(PYFARM, "cfg", "logger.xml")

def settings(config=XML_CONFIG):
    '''
    Read in an xml file and return a settings dictionary for use
    by the logger

    @param path: The path to the xml configuration file
    @type  path: C{str}
    '''
    if not os.path.isfile(config):
        raise IOError('%s is not a vaild file path' % config)

    try:
        dom = xml.etree.ElementTree.parse(config)

    except Exception, error:
        print "ERROR: %s" % error
        sys.exit(1)

    # setup initial values
    data   = {}
    root   = dom.getroot()

    # read in the global settings
    settings = data['settings'] = {}
    for mainSetting in root.findall("globals/attr"):
        key   = mainSetting.attrib['name']
        value = mainSetting.attrib['value']

        # attempt to convert the value to something usable
        value = str(value)

        ##...to bool
        if value == "False":  value = False
        elif value == "True": value = True

        ## ...to float
        elif "." in value:
            try:                     value = float(value)
            except Exception, error: None

        ## ...to integer
        else:
            try:                         value = int(value)
            except Exception, error:     None

        # add the global setting
        settings[key] = value

    # read the level settings
    count  = 0
    levels = data['levels'] = []
    for levelSettings in root.findall("levels/level"):
        setting          = levelSettings.attrib
        setting['level'] = count

        # add any missing function name
        if not setting.has_key("function"):
            setting['function'] = setting['name']

        # setup enabled key
        if count <= settings['defaultLevel']:
            setting['enabled'] = False

        elif setting.has_key("enabled") and setting['enabled'] == "True":
            setting['enabled'] = True

        elif setting.has_key("enabled") and setting['enabled'] == "False":
            setting['enabled'] = False

        else:
            setting['enabled'] = True

        # setup solo
        if not settings['allowSolo']:
            setting['solo'] = False

        elif setting.has_key("solo") and setting['enabled'] == "True":
            setting['solo'] = True

        else:
            setting['solo'] = False

        levels.append(setting)
        count += 1

    return data


class History(UserList.UserList):
    '''
    Holds onto the history of a log object

    @param enabled: Determines if history will be logged
    @type  enabled: C{bool}
    @param maxLength: The max length of the history log
    @type  maxLength: C{int}
    '''
    def __init__(self, enabled=False, maxLength=500):
        self.data      = []
        self.enabled   = enabled
        self.maxLength = maxLength

    def __repr__(self):
        return os.linesep.join(self)

    def append(self, value):
        '''Append line to self.data while respecting the max history length'''
        if self.enabled:
            # remove index zero if we have already reached the max log size
            if len(self) >= self.maxLength:
                self.pop(0)

            self.data.append(str(value))

    def clear(self):
        '''Clear all history'''
        self.data = []


class Level(object):
    '''
    Container object which hold the log attribute and log history

    @param root: The root log object
    @type  root: logger.Logger
    @param settings: The settings to assign to the log
    @type  settings: C{dict}
    @param tempalte: String template for output
    @type  template: string.Template
    @param history: Enable to disable history
    @type  history: C{bool}
    @param maxHistory: The max number of lines to maintain in history
    @type  maxHistory: C{int}
    '''
    def __init__(self, root, settings, history=False, maxHistory=500):
        self.history = History(enabled=history, maxLength=maxHistory)
        self.root     = root
        self.logName  = root.name
        self.template = root.template

        # assign all keys in settings dictionary to class attributes
        for key, value in settings.items():
            setattr(self, key, value)


    def _moduleName(self, stack):
        '''Given the inspection stack return the module name'''
        path   = stack[0].f_code.co_filename
        split  = path.split(os.sep)
        module =  split[-1].split(".")[0]

        if len(split) > 1:
            split  = split[-2:]
            module = split[0]+"."+module

        return module

    def __call__(self, text, force=False):
        output = ''

        if self.root.enabled and (self.enabled or force):
            self.history.append(text)

            # prepare the log message
            stack  = inspect.stack()[1]
            path   = stack[0].f_code.co_filename
            split  = path.split(os.sep)
            module = split[-1].split(".")[0]

            if len(split) > 1:
                split  = split[-2:]
                lib    = split[0]
                module = lib+"."+module

            else:
                module = split[0].split(".")[0]
                lib    = ''

            # convert template into formatted string
            # NOTE: Be sure that any variables used here are defined in the
            # logging configuration file
            output = self.template.substitute(
                                                lib=lib,
                                                module=module,
                                                line=stack[0].f_lineno,
                                                level=self.name.upper(),
                                                message=text
                                            )
            print output

        return output # return the output as well for processing if needed


class Logger(object):
    '''
    Main logger module that ties the log settings and log level together into
    one object.

    NOTE: Name and level are legacy variables and are not being used at this
    time.
    '''
    def __init__(self, name='PyFarm', level=None):
        self.settings = settings()
        self.enabled  = True
        self.name     = name
        self.levels   = []
        self.template = string.Template(self.settings['settings']['template'])

        for level in self.settings['levels']:
            log = Level(self, level)
            setattr(self, level['function'], log)
            self.levels.append(log)

    def setEnabled(self, enabled):
        '''
        Set the logger as either enabled or disabled.  This function is made
        avaliable for use by PyQts signals and slots.
        '''
        self.enabled = enabled

    def setLevel(self, level):
        '''
        Set the overall log level.  This function is made avaliable for use
        by PyQts signals and slots.
        '''
        self.level = level

if __name__ == '__main__':
    print "Testing logger"
    logger = Logger()

    for level in logger.levels:
        level.__call__("%s says hello" % level.name)
