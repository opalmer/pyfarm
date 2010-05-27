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
import sys
import ConfigParser

# From PyFarm
#from lib.Logger import Logger

__MODULE__ = "ParseConfig.py"
__LOGLEVEL__ = 4

class ReadConfig(object):
    '''Read in and perform basic operations on configuration files'''
    def __init__(self, config, skipSoftware=True):
        self.config = config
        self.servers = self.configServers()
        self.database = self.configDatabase()
        self.logging = self.configLogging()

        # searching for software takes time, so we can skip
        #  it if we really need to.
        if not skipSoftware:
            self.software = self.configSoftware()

    def configServers(self):
        '''Return a dictionary of servers and their port numbers'''
        out = {}
        return out

    def configDatabase(self):
        '''Return a dictionary with database information'''
        out = {}
        return out

    def configLogging(self):
        '''Return a dictionary with log information'''
        out = {}
        return out

    def configSoftware(self):
        '''Find (if necessary) and return a software dictionary'''
        out = {}
        return out

if __name__ != "__MAIN__":
    ini = sys.argv[1]
    cfg = ConfigParser.ConfigParser()
    cfg.read(ini)
    print cfg.items('drivers')
    for path in cfg.items('search-paths'):
        print path