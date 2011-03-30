'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 28 2010
PURPOSE: To query and return information about the local system

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

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
import re
import os
import sys
import socket
import fnmatch
import platform
import tempfile

from PyQt4 import QtNetwork, QtCore

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", ".."))
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib.system import process
from lib import logger, settings, decorators

logger = logger.Logger()

if os.name == "nt":
    USER     = os.getenv('USERNAME')
    HOME     = os.getenv('USERPROFILE', tempfile.gettempdir())
    HOSTNAME = os.getenv('COMPUTERNAME', socket.gethostname())

else:
    USER     = os.getenv('USER')
    HOME     = os.getenv('HOME', tempfile.gettempdir())
    HOSTNAME = os.getenv('HOSTNAME', socket.gethostname())

PYFARMHOME = os.path.join(HOME, '.pyfarm')

class SystemInfo(object):
    '''Default system information object to query and store info about hardware and software'''
    @decorators.deprecated
    def __init__(self, configDir, skipSoftware=True):
        logger.deprecated("Depcecated in favor of module level functions!!!")
        if os.name == "nt":
            cache = None
        else:
            cache = None

        self.config   = settings.ReadConfig.general(configDir)
        self.hardware = Hardware(cache)
        self.network  = Network()
        logger.deprecated("Depcecated in favor of module level functions!!!")

    @staticmethod
    @decorators.deprecated
    def tmpdir(children=[]):
        '''Given a list of children, return a full temporary path'''
        path = TMPDIR
        for child in children:
            path = os.path.join(path, child)
        return path


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
                self.rammax = float(process.SimpleCommand("free | grep Mem | awk '{print $2}'"))/1024
                self.swapmax = float(process.SimpleCommand("free | grep Swap | awk '{print $2}'"))/1024

            except TypeError:
                logger.fixme("Hardware information (linux) not implimented for new SimpleCommand")
                self.rammax  = None
                self.swapmax = None

            except ValueError, e:
                logger.fixme("Invalid output from SimpleCommand")

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
            results = float(process.SimpleCommand("free | grep 'buffers/cache' | awk '{print $3}'"))/1024
        else:
            logger.notimplemented("Ram used not implemented for %s" % os.name)

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
            results = float(process.SimpleCommand("free | grep Swap | awk '{print $3}'"))/1024
        else:
            logger.notimplemented("swap used not implemented for %s" % os.name)

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
            logger.notimplemented("CPU Count not implemented without multiprocessing")

        return results

    def cpuload(self):
        '''Return cpu load averages for 1,5,15 mins'''
        results = None
        if os.name == "posix":
            results = open('/proc/loadavg').readlines()[0].split()[:3]
        else:
            logger.notimplemented("cpuload not implemented for %s" % os.name)

        return results

    def uptime(self):
        '''Return uptime information'''
        results = None
        if os.name == "posix":
            results = float(open('/proc/uptime').readlines()[0].split()[0])
        else:
            logger.notimplemented("uptime not implemented for %s" % os.name)

        return results

    def idletime(self):
        '''Return total idle time'''
        results = None
        if os.name == "posix":
            results = float(open('/proc/uptime').readlines()[0].split()[1])
        else:
            logger.notimplemented("idletime not implemented for %s" % os.name)

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

    def _ip(self):
        '''Return the best guess ip via qt'''
        for iface in QtNetwork.QNetworkInterface.allInterfaces():
            for addr in iface.addressEntries():
                hostAddr = addr.ip()
                if hostAddr.protocol() == QtNetwork.QAbstractSocket.IPv4Protocol:
                    ip = hostAddr.toString()
                    if self._validAddress(ip):
                        return ip

    def ip(self):
        '''Return the ip address'''
        return self._ip()

    def subnet(self):
        '''Return subnet information for the adapter'''
        results = None
        if os.name == "posix":
            query = "ifconfig %s | grep 'inet addr' | gawk -F: '{print $4}' | gawk '{print $1}'" % self.adapter
            results = process.SimpleCommand(query)
        else:
            logger.notimplemented("subnet not implemented for %s" % os.name)

        return results

    def _validAddress(self, ip):
        '''
        Return true if the address is considered valid.
        Addresses matching localhost ips will not be considered.
        '''
        if not re.match(r'''127[.]0[.][0-9][.][0-9]''', ip):
            return True
        else:
            return False

    def hostname(self):
        '''Return the hostname'''
        return HOSTNAME


class Qt(object):
    '''Setup and return information about Qt'''
    VERSION_STR   = QtCore.QT_VERSION_STR
    VERSION_MAJOR = int(VERSION_STR.split('.')[0])
    VERSION_MINOR = int(VERSION_STR.split('.')[1])


class PyQt(object):
    '''Setup and return information about PyQt'''
    VERSION_STR   = QtCore.PYQT_VERSION_STR
    VERSION_MAJOR = int(VERSION_STR.split('.')[0])
    VERSION_MINOR = int(VERSION_STR.split('.')[1])

# cleanup objects
del CWD, PYFARM
