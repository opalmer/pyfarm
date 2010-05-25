'''
HOMEPAGE: www.pyfarm.net
INITIAL: Sept 25 2009
PURPOSE: Small library for discovering system info and installed software

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
import os, sys, py_compile, fnmatch

# From PyFarm
from lib.Logger import Logger
from Info import System, bold, find
from ReadSettings import ParseXmlSettings

__MODULE__ = "lib.InputFlags"
__LOGLEVEL__ = 4

class SystemInfo(object):
    '''Gather and prepare to return info about the system'''
    def __init__(self):
        self.cwd = os.getcwd()
        self.log = Logger("InputFlags.SystemInfo", __LOGLEVEL__)

    def system(self, option=None, opt=None, value=None, parser=None):
        '''Echo only system information to the command line'''
        self.log.debug("Getting system info")
        system = System(self.logger, self.logLevels)
        out = "\nOS Type : %s" % system.os()[0]
        out += "\nOS Architecture : %s" % system.os()[1]
        out += "\nHostname : %s" % system.hostname()
        out += "\nCPU Count: %s" % system.cpuCount()
        out += "\nRAM Total: %i" % system.ramTotal()
        self.log.log(self.logLevels["FIXME"], "Incorrect free ram value")
        out += "\nRAM Free: %i" % system.ramFree()
        self.log.log(self.logLevels["FIXME"], "Incorrect total swap value")
        out += "\nSWAP Total: %i" % system.swapTotal()
        self.log.log(self.logLevels["FIXME"], "Incorrect free swap value")
        out += "\nSWAP Free: %i" % system.swapFree()
        self.log.debug("Returning system info")
        print out
        sys.exit(0)

    def software(self, option=None, opt=None, value=None, parser=None):
        '''Echo only installed software information to the command line'''
        self.log.debug("Getting software info")
        out = "\nInstalled Software: "+bold(0)
        count = 0

        # find the software and add it to the output
        self.software = ParseXmlSettings('./cfg/settings.xml').installedSoftware()
        for software in self.software:
            if count < len(self.software)-1:
                out += "%s, " % software
            else:
                out += "%s" % software
            count += 1

        self.log.debug("Returning software info")
        print out
        sys.exit(0)


class SystemUtilities(object):
    '''General system utilities to run from the command line'''
    def __init__(self):
        self.cwd = os.getcwd()
        self.log = Logger("InputFlags.SystemUtilities", __LOGLEVEL__)

    def clean(self, option=None, opt=None, value=None, parser=None):
        '''Cleanup any extra or byte-compiled files'''
        self.log.debug("Running clean")
        for pyc in find("*.pyc", os.getcwd()):
            os.remove(pyc)
        self.log.debug("Clean complete")
        sys.exit(0)
