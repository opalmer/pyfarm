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
import socket
import fnmatch
import platform

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
        if os.name == "nt":
            #process = SimpleCommand("cmd.exe /C systeminfo", all=False)
            #cache   = str(process.readAll())
            log.notimplemented("SystemInfo not implemented for %s" % os.name)
            cache = None

        else:
            cache = None

        self.config = ReadConfig(configDir)
        self.hardware = Hardware(cache)
        self.software = Software(self.config, cache)
        #self.network = Network(self.config.netadapter, cache)

class OperatingSystem(object):
    '''Query and return information about the operating system'''
    def __init__(self, cache):
        self.type  = os.name
        self.cache = cache

    def version(self):
        '''Return the version of the given os'''
        pass

    def architecture(self):
        '''Return the architecure type of the given os'''
        pass


class Hardware(object):
    '''Used to query information about the local hardware'''
    def __init__(self, cache):
        self.cache   = cache
        self.rammax  = 0
        self.swapmax = 0

        if os.name == "posix":
            try:
                self.rammax = float(SimpleCommand("free | grep Mem | awk '{print $2}'"))/1024
                self.swapmax = float(SimpleCommand("free | grep Swap | awk '{print $2}'"))/1024

            except TypeError:
                log.fixme("Hardware information (linux) not implimented for new SimpleCommand")
                self.rammax  = None
                self.swapmax = None

        elif os.name == "nt":
            print self.cache

    def _toGigabyte(self, value, toGigabyte):
        '''If requested, convert to gigabytes'''
        if value and toGigabyte:       return value/1024
        elif value and not toGigabyte: return int(value)
        else:                          return None

    def ramtotal(self, toGigabyte=False):
        '''Return the total amout of installed ram'''
        return self._toGigabyte(self.rammax, toGigabyte)

    def ramused(self, toGigabyte=False):
        '''Return the amout of ram used'''
        results = None
        if os.name == "posix":
            results = float(SimpleCommand("free | grep 'buffers/cache' | awk '{print $3}'"))/1024
        else:
            log.notimplemented("Ram used not implemented for %s" % os.name)

        return self._toGigabyte(results, toGigabyte)

    def ramfree(self, toGigabyte=False):
        '''Return the amount of free ram'''
        return self._toGigabyte(self.rammax-self.ramused(), toGigabyte)

    def swaptotal(self, toGigabyte=False):
        '''Return the total amount of swap'''
        return self._toGigabyte(self.swapmax, toGigabyte)

    def swapused(self, toGigabyte=False):
        '''Return the total amout of swap used'''
        results = None
        if os.name == "posix":
            results = float(SimpleCommand("free | grep Swap | awk '{print $3}'"))/1024
        else:
            log.notimplemented("swap used not implemented for %s" % os.name)

        return self._toGigabyte(results, toGigabyte)

    def swapfree(self, toGigabyte=False):
        '''Return amout of swap free'''
        return self._toGigabyte(self.swapmax-self.swapused(), toGigabyte)

    def cpucount(self):
        '''Return the cpu count'''
        results = None
        try:
            import multiprocessing
            results = str(multiprocessing.cpu_count())

        except ImportError:
            log.notimplemented("CPU Count not implemented without multiprocessing")

        return results

    def cpuload(self):
        '''Return cpu load averages for 1,5,15 mins'''
        results = None
        if os.name == "posix":
            results = open('/proc/loadavg').readlines()[0].split()[:3]
        else:
            log.notimplemented("cpuload not implemented for %s" % os.name)

        return results

    def uptime(self):
        '''Return uptime information'''
        results = None
        if os.name == "posix":
            results = float(open('/proc/uptime').readlines()[0].split()[0])
        else:
            log.notimplemented("uptime not implemented for %s" % os.name)

        return results

    def idletime(self):
        '''Return total idle time'''
        results = None
        if os.name == "posix":
            results = float(open('/proc/uptime').readlines()[0].split()[1])
        else:
            log.notimplemented("idletime not implemented for %s" % os.name)

        return results


class Software(object):
    '''Query and return information about the software on the local system'''
    def __init__(self, config=None, cache=None):
        self.config = config
        self.cache  = cache


class Network(object):
    '''Query and return information about the local network'''
    def __init__(self, adapter=None, cache=None):
        self.adapter = adapter
        self.cache   = cache

    def ip(self):
        '''Return the ip address'''
        results = None
        if os.name == "posix":
            query = "ifconfig %s | grep 'inet addr' | gawk -F: '{print $2}' | gawk '{print $1}'" % self.adapter
            results = SimpleCommand(query)
        else:
            results = socket.gethostbyname(self.hostname())

        return results

    def subnet(self):
        '''Return subnet information for the adapter'''
        results = None
        if os.name == "posix":
            query = "ifconfig %s | grep 'inet addr' | gawk -F: '{print $4}' | gawk '{print $1}'" % self.adapter
            results = SimpleCommand(query)
        else:
            log.notimplemented("subnet not implemented for %s" % os.name)

        return results

    def _validateHostname(self, name):
        '''
        Ensure that the given name is a proper hostname
        if not attempt to return a more valid entry
        '''
        if not name or fnmatch.fnmatch(name, "*localhost*"):
            name = platform.node()

        if not name or fnmatch.fnmatch(name, "*localhost*"):
            try:    name = socket.getfqdn()
            except: name = None

        return name

    def hostname(self):
        '''Return the hostname'''
        results = None
        if os.name == "posix" or os.name == "nt":
            results = SimpleCommand("hostname")

        else:
            try:    results = socket.getfqdn()
            except: results = None

        return self._validateHostname(results)

    def mac(self):
        '''Return mac address for the adapter'''
        results = None
        if os.name == "posix":
            results = SimpleCommand("ifconfig %s | grep 'Link encap' | awk '{print $5}'" % self.adapter)
        else:
            log.notimplemented("mac not implemented for %s" % os.name)

        return results
