'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 26 2010
PURPOSE: To provide a means for configuration parsing and easy integration of
third party software packages.

This file is part of PyFarm.
Copyright (C) 2008-2010 Oliver Palmer

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


class Software(object):
    '''Find and return information about software installed on the system'''
    def __init__(self): pass


class Export(object):
    '''Used to export settings to an external file'''
    def __init__(self):
       pass

    def write(self):
        '''Out the current settings'''
        pass
