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
import os
import sys

# From PyFarm
from lib.Logger import Logger
from lib.system.Info import Hardware

MODULE = "lib.InputFlags"
LOGLEVEL = 4

log = Logger(__MODULE__, __LOGLEVEL__)

class SystemInfo(object):
    '''Gather and prepare to return info about the system'''
    def __init__(self):
        self.cwd = os.getcwd()
        self.log = Logger("InputFlags.SystemInfo", __LOGLEVEL__)

    def showinfo(self, option=None, opt=None, value=None, parser=None):
        '''Return all information about the system'''
        hardware = Hardware()
        load = hardware.cpuload()
        print "Hardware Information:"
        print "\tCPU Count: %i" % hardware.cpucount()
        print "\tCPU Load Averages: %s, %s, %s" % (load[0], load[1], load[2])
        print "\tUptime: %.2f hr" % (hardware.uptime()/3600)
        print "\tIdle Time: %.2f hr" % (hardware.idletime()/3600)
        print "\n\tMemory (RAM):"
        print "\t\tTotal: %.2f GB" % hardware.ramtotal(1)
        print "\t\tUsed: %.2f GB" % hardware.ramused(1)
        print "\t\tFree: %.2f GB" % hardware.ramfree(1)
        print "\n\tMemory (Swap):"
        print "\t\tTotal: %.2f GB" % hardware.swaptotal(1)
        print "\t\tUsed: %.2f GB" % hardware.swapused(1)
        print "\t\tFree: %.2f GB" % hardware.swapfree(1)

        #for stat in hardware.
        self.log.terminate("Program terminated by command line flag")

    def software(self, option=None, opt=None, value=None, parser=None):
        '''Echo only installed software information to the command line'''
        self.log.debug("Getting software info")
        out = "\nInstalled Software: "
        count = 0

        # find the software and add it to the output
        self.software = ParseXmlSettings('./cfg/settings.xml', 'cmd',skipSoftware=False).installedSoftware()
        if not len(self.software):
            out = "No software installed"
        else:
            for software in self.software:
                if count < len(self.software)-1:
                    out += "%s, " % software
                else:
                    out += "%s" % software
                count += 1

        self.log.debug("Returning software info")
        print out
        self.log.terminate("Program terminated by command line flag")


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
        self.log.terminate("Program terminated by command line flag")


class About(object):
    '''Store and return information about PyFarm itself'''
    def __init__(self, dev, gpl):
        self.dev = dev

        try:
            self.gpl = open(gpl, 'r')
        except IOError:
            self.gpl = 'Could not find license: %s' % gpl

    def author(self, option=None, opt=None, value=None, parser=None):
        '''Return the author's name'''
        log.info("Developed By: %s" % self.dev)
        log.terminate("Program terminated by command line flag")

    def license(self, option=None, opt=None, value=None, parser=None):
        '''Return the gpl header'''
        for line in self.gpl:
            print line.strip()
        log.terminate("Program terminated by command line flag")
