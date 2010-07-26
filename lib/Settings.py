'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 26 2010
PURPOSE: To provide a means for configuration parsing and easy integration of
third party software packages.

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
# From Python
import os
import sys
import os.path
import ConfigParser
from xml.dom import minidom

# From PyFarm
#from lib.Logger import Logger

__MODULE__ = "ParseConfig.py"

def ReadConfig(cfgInput):
    '''Parse a config file and return a data dictionary'''
    #log = Logger("Settings.ReadConfig")
    if os.path.isfile(cfgInput):
        out = {}
        cfg = ConfigParser.ConfigParser()
        cfg.read(cfgInput)

        for section in cfg.sections():
            out[section] = {}
            for option in cfg.options(section):
                if section == "servers": out[section][option] = cfg.getint(section, option)
                elif section == "network-adapter": out[section][option] = cfg.get(section, option)
                elif section == "broadcast": out[section][option] = cfg.getint(section, option)
                elif section == "database": out[section][option] = cfg.get(section, option)
                elif section == "logging":
                    if option == "enable": cfg.getboolean(section, option)
                    else: out[section][option] = cfg.get(section, option)

        #log.debug("Read configuration file")
        return out
    else:
        raise IOError("%s is not a valid config file" % cfgInput)

class Software(object):
    '''Find and return information about software installed on the system'''
    def __init__(self): pass


def ConfigLogger(xml):
    '''Read the logging configuration file and return the required information'''
    out = {}
    xml = minidom.parse(xml)

    for element in xml.getElementsByTagName("level"):
        level = int(element.getAttribute("value"))
        name = str(element.getAttribute("name"))

        # function name
        if element.hasAttribute("function"):
            function = str(element.getAttribute("function"))
        else:
            function = name

        # terminal color (linux only)
        if element.hasAttribute("color") and os.name == 'posix':
            color = str(element.getAttribute("color"))
        else:
            color = ''

        # bold attribute
        if element.hasAttribute("bold") and os.name == 'posix':
            bold = 'BOLD_VALUE' # this should really be getting the bold VALUE
        else:
            bold = ''

        # place element into output dictionary
        out[level] = {
                            'name' : name,
                            'function' : function,
                            'color' : color,
                            'bold' : bold,
                            'enabled' : eval(element.getAttribute("enabled"))
                        }
    return out


