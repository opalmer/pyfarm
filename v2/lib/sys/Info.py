'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 28 2010
PURPOSE: To query and return information about the local system

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
import multiprocessing

# From PyFarm
from lib.Settings import ReadConfig

__MODULE__ = 'lib.sys.SysInfo'
__LOGLEVEL__ = 6

class SystemInfo(object):
    '''Default system information object to query and store info about hardware and software'''
    def __init__(self, configDir, skipSoftware=True):
        self.config = ReadConfig(configDir)
        self.hardware = Hardware()
        self.software = Software(self.config)
        self.network = Network(self.config.netadapter)

class Hardware(object):
    '''Used to query information about the local hardware'''
    #
    #
    # REAING IN FROM /PROC WOULD
    # BE PREFERRED!!!!
    #
    # find the proc file: man <command>
    # example: man free returns /proc/meminfo
    #
    def ramtotal(self):
        '''Return the total amout of installed ram'''
        # free | grep Mem | awk '{print $2}'
        pass

    def ramused(self):
        '''Return the amout of ram used'''
        # free | grep Mem | awk '{print $3}'
        pass

    def swapfree(self):
        '''Return amout of ram free'''
        pass

    def swaptotal(self):
        '''Return the total amount of swap'''
        # cat /proc/swaps | grep partition | awk '{print $3}'
        pass

    def swapused(self):
        '''Return the total amout of swap used'''
        # cat /proc/swaps | grep partition | awk '{print $4}'
        pass

    def swapfree(self):
        '''Return amout of swap free'''
        pass

    def cpucount(self):
        '''Return the cpu count'''
        return multiprocessing.cpu_count()

    def cpuusage(self):
        '''Return cpu usage for 1,5,15 mins'''
        # 1 min avg: uptime | awk '{print $8'}
        # 5 min avg: uptime | awk '{print $9'}
        # 15 min av: uptime | awk '{print $10'}
        pass

    def uptime(self):
        '''Return uptime information'''
        # uptime | awk '{print $1'}
        pass


class Software(object):
    '''Query and return information about the software on the local system'''
    def __init__(self, config):
       pass

class Network(object):
    '''Query and return information about the local network'''
    def __init__(self, adapter):
        self.adapter = adapter

    def ip(self):
        '''Return the ip address'''
        # ifconfig <adapter> | grep "inet addr" | gawk -F: '{print $2}' | gawk '{print $1}'
        pass

    def subnet(self):
        '''Return subnet information for the adapter'''
        # ifconfig <adapter> | grep "inet addr" | gawk -F: '{print $4}' | gawk '{print $1}'
        pass

    def hostname(self):
        '''Return the hostname'''
        # hostname
        pass

    def mac(self):
        '''Return mac address for the adapter'''
        # ifconfig <adapter> | grep "Link encap" | awk '{print $5}'
        pass
