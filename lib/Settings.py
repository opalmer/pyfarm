'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 26 2010
PURPOSE: To provide a means for configuration parsing and easy integration of
third party software packages.

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
import ConfigParser
from xml.dom import minidom

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

class ReadConfig(object):
    '''Parse various configuration files'''
    @staticmethod
    def test(filepath):
        if os.path.isfile(filepath):
            return filepath
        else:
            raise IOError("%s is not a valid config file" % cfgInput)

    @staticmethod
    def general(filepath):
        '''Return the general configuration file after reading'''
        out = {}
        cfg = ConfigParser.ConfigParser()
        cfg.read(ReadConfig.test(filepath))

        for section in cfg.sections():
            out[section] = {}
            for option in cfg.options(section):
                if section == "servers":
                    out[section][option] = cfg.getint(section, option)

                elif section == "network-adapter":
                    out[section][option] = cfg.get(section, option)

                elif section == "broadcast":
                    out[section][option] = cfg.getint(section, option)

                elif section == "database":
                    out[section][option] = cfg.get(section, option)

                elif section == "logging":
                    if option == "enable":
                        cfg.getboolean(section, option)
                    else:
                        out[section][option] = cfg.get(section, option)
        return out

    @staticmethod
    def logger(filepath):
        '''
        Given an xml file, return a dictionary with the configuration
        for the Logger object
        '''
        out = {}
        xml = minidom.parse(ReadConfig.test(filepath))

        level = 0
        for element in xml.getElementsByTagName("level"):
            name    = str(element.getAttribute("name"))
            enabled = eval(element.getAttribute("enabled"))
            solo    = eval(element.getAttribute("solo"))

            # function name
            if element.hasAttribute("function"):
                function = str(element.getAttribute("function"))
            else:
                function = name

            # place element into output dictionary
            out[name] = {
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


class StatusLevel(object):
    '''
    Individual status level containing specific
    information pretaining to a status level
    '''
    def __init__(self, dom):
        self.id   = int(dom.getAttribute("id"))
        self.name = dom.getAttribute("string")
        self.fg   = dom.getAttribute("colorFG")
        self.bg   = dom.getAttribute("colorBG")


class Status(object):
    '''Given the status xml, read and return individual status levels'''
    def __init__(self, config):
        self.levels = {}
        self.config = minidom.parse(config)

        for lvl in self.config.getElementsByTagName("level"):
            level = StatusLevel(lvl)
            self.levels[level.id] = level

    def level(self, levelId):
        '''Return a status level'''
        if type(levelId) == int:
            return self.levels[levelId]

        elif type(levelId) == str:
            for level in self.levels:
                if levelId.upper() == level.name.upper():
                    return level
