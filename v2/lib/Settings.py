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
import ConfigParser

# From PyFarm
#from lib.Logger import Logger

__MODULE__ = "ParseConfig.py"
__LOGLEVEL__ = 4

class ReadConfig(object):
    '''Read in and perform basic operations on configuration files'''
    def __init__(self, configDir, skipSoftware=True):
        # read in configs
        self.config = "%s/general.ini" % configDir
        self.softwareConfigs = [ cfg for cfg in os.listdir("%s/software" % configDir) ]
        self.parser = ConfigParser.ConfigParser()
        self.parser.read(self.config)

        # establish settings
        self.servers = self.configServers()
        self.broadcast = self.configBroadcast()
        self.database = self.configDatabase()
        self.logging = self.configLogging()

        # searching for software takes time, so we can skip
        #  it if we really need to.
        if not skipSoftware:
            self.software = self.configSoftware()

    def _intConvert(self, makeInt, value):
        '''If requested, return an integer value'''
        if makeInt:
            return int(value)
        else:
            return value

    def _config(self, item, intVals=False, checkEmpty=False):
        '''
        Return a dictionary using the selected item

        VARIABLES:
            item (str) -- Item to parse in config
            intVals (bool) -- If enabled convert all retrieved
            values to integers
            checkEmpty (bool) -- check for empty values [None, False, 0, etc.].
            If the value is empty, do not add it to the dictionary
        '''
        out = {}
        for key,value in self.parser.items(item):
            if not checkEmpty:
                out[key] = self._intConvert(intVals, value)
            elif checkEmpty and value not in ("NONE","FALSE","NO","0"):
                out[key] = self._intConvert(intVals, value)
        return out

    def configServers(self):
        '''Return a dictionary of servers and their port numbers'''
        return self._config('servers', intVals=True)

    def configBroadcast(self):
        '''Return a dictionary with broadcast settings'''
        return self._config('broadcast', intVals=True)

    def configDatabase(self):
        '''Return a dictionary with database information'''
        return self._config('database', checkEmpty=True)

    def configLogging(self):
        '''Return a dictionary with log information'''
        return self._config('logging', checkEmpty=True)

    def configSoftware(self):
        '''Find (if necessary) and return a software dictionary'''
        out = {}
        return out