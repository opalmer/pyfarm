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
# From Python
import os
import sys
import string
import ConfigParser
from xml.dom import minidom

# From PyFarm
#from lib.Logger import Logger

MODULE = "ParseConfig.py"

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

    else:
        raise IOError("%s is not a valid config file" % cfgInput)


class Software(object):
    '''Find and return information about software installed on the system'''
    def __init__(self): pass


class Storage(object):
    '''
    Create a direcectory and/or ensure that session
    information for PyFarm can be stored locally for
    use by PyFarm.

    >>> storage = Storage()
    >>> storate.createDirs()
    >>> storate
    '''
    def __init__(self):
       self.prefsRoot = os.path.join(os.getenv("HOME"), ".pyfarm")

    def createDirs(self):
        '''Create preference directories'''
        if not os.path.isdir(self.prefsRoot):
            os.mkdirs(self.prefsRoot)

        for dirname in ("pids", ):
            os.mkdir(os.path.join(self.prefsRoot, dirname))

if __name__ == '__main__':
    import doctest
    print "Doc Testing: %s" % os.path.abspath(__file__)
    doctest.testmod()