'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 28 2010
PURPOSE: To query and return information about the local system

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
import multiprocessing

# From PyFarm
from lib.Logger import Logger
from lib.Settings import ReadConfig
from lib.system.Utility import SimpleCommand

MODULE   = 'lib.sys.SysInfo'
LOGLEVEL = 6

log = Logger(MODULE, LOGLEVEL)

class SystemInfo(object):
    '''Default system information object to query and store info about hardware and software'''
    def __init__(self, configDir, skipSoftware=True):
        self.config = ReadConfig(configDir)
        self.hardware = Hardware()
        self.software = Software(self.config)
        #self.network = Network(self.config.netadapter)

class OperatingSystem(object):
    '''Query and return information about the operating system'''
    def __init__(self):
        pass

    def os(self):
        '''Return the type of os'''
        types = {
                 'posix': 'linux',
                 'nt' : 'windows',
                 'mac' : 'mac'
                }

        try:
            return types[os.name]

        except KeyError:
            log.fatal("%s is not an UNSUPPORTED operating system" % os.name)

    def version(self):
        '''Return the version of the given os'''
        pass

    def architecture(self):
        '''Return the architecure type of the given os'''
        pass


class Hardware(object):
    '''Used to query information about the local hardware'''
    def __init__(self):
        # since these are constant values. store them for later use
        if os.name == "posix":
            self.rammax = float(SimpleCommand("free | grep Mem | awk '{print $2}'"))/1024
            self.swapmax = float(SimpleCommand("free | grep Swap | awk '{print $2}'"))/1024

        elif os.name == "nt":
            process = SimpleCommand("cmd.exe /C systeminfo /FO CSV", all=False)
            for i in process.readAll().split(","):
                print i

    def _toGigabyte(self, value, toGigabyte):
        '''If requested, convert to gigabytes'''
        if toGigabyte:
            return value/1024
        else:
            return int(value)

    def ramtotal(self, toGigabyte=False):
        '''Return the total amout of installed ram'''
        return self._toGigabyte(self.rammax, toGigabyte)

    def ramused(self, toGigabyte=False):
        '''Return the amout of ram used'''
        mb = float(SimpleCommand("free | grep 'buffers/cache' | awk '{print $3}'"))/1024
        return self._toGigabyte(mb, toGigabyte)

    def ramfree(self, toGigabyte=False):
        '''Return the amount of free ram'''
        return self._toGigabyte(self.rammax-self.ramused(), toGigabyte)

    def swaptotal(self, toGigabyte=False):
        '''Return the total amount of swap'''
        return self._toGigabyte(self.swapmax, toGigabyte)

    def swapused(self, toGigabyte=False):
        '''Return the total amout of swap used'''
        mb = float(SimpleCommand("free | grep Swap | awk '{print $3}'"))/1024
        return self._toGigabyte(mb, toGigabyte)

    def swapfree(self, toGigabyte=False):
        '''Return amout of swap free'''
        return self._toGigabyte(self.swapmax-self.swapused(), toGigabyte)

    def cpucount(self):
        '''Return the cpu count'''
        return multiprocessing.cpu_count()

    def cpuload(self):
        '''Return cpu load averages for 1,5,15 mins'''
        return open('/proc/loadavg').readlines()[0].split()[:3]

    def uptime(self):
        '''Return uptime information'''
        return float(open('/proc/uptime').readlines()[0].split()[0])

    def idletime(self):
        '''Return total idle time'''
        return float(open('/proc/uptime').readlines()[0].split()[1])


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
        query = "ifconfig %s | grep 'inet addr' | gawk -F: '{print $2}' | gawk '{print $1}'" % self.adapter
        return SimpleCommand(query)

    def subnet(self):
        '''Return subnet information for the adapter'''
        query = "ifconfig %s | grep 'inet addr' | gawk -F: '{print $4}' | gawk '{print $1}'" % self.adapter
        return SimpleCommand(query)

    def hostname(self):
        '''Return the hostname'''
        return SimpleCommand("hostname")

    def mac(self):
        '''Return mac address for the adapter'''
        return SimpleCommand("ifconfig %s | grep 'Link encap' | awk '{print $5}'" % self.adapter)
