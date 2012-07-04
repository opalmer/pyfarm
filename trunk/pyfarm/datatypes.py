# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import sys
import psutil
import socket
import netifaces

from sqlalchemy.types import String

class OperatingSystem:
    LINUX, WINDOWS, MAC, OTHER = range(4)
    MAPPINGS = {
        "windows" : WINDOWS,
        "cygwin" : WINDOWS,
        "darwin" : MAC,
        "linux" : LINUX,
        "mac" : MAC,
        WINDOWS : "windows",
        LINUX : "linux",
        MAC : "mac"
    }

    @staticmethod
    def get(value=None):
        '''
        returns the current operating system as an integer or the assoicated
        entry for the given value

        :exceptoion KeyError:
            raised if value is not None and is not in OperatingSystem.MAPPINGS
        '''
        if isinstance(value, (int, str, unicode)):
            return OperatingSystem.MAPPINGS[value]

        platform = sys.platform
        if platform.startswith("linux"):
            platform = "linux"

        elif platform.startswith("win"):
            platform = "windows"

        elif platform not in OperatingSystem.MAPPINGS:
            return OperatingSystem.OTHER

        return OperatingSystem.MAPPINGS[platform]
    # end get
# end OperatingSystem

class __Localhost:
    '''namespaced class for storing information about the local host'''

    # constants which do not change at runtime
    OS = OperatingSystem.get()
    OSNAME = OperatingSystem.MAPPINGS.get(OS)
    CPU_COUNT = psutil.NUM_CPUS
    TOTAL_RAM = int(psutil.phymem_usage().total / 1024 / 1024)
    TOTAL_SWAP = int(psutil.virtmem_usage().total / 1024 / 1024)

    class __network:
        @property
        def SENT(self): return psutil.network_io_counters().bytes_sent / 1024 / 1024
        @property
        def RECV(self): return psutil.network_io_counters().bytes_recv / 1024 / 1024
        @property
        def HOSTNAME(self): return socket.gethostname()
        @property
        def FQDN(self): return socket.getfqdn(self.HOSTNAME)
        @property
        def IP(self): return socket.gethostbyname(self.HOSTNAME)

        @property
        def SUBNET(self):
            '''returns the current subnet address'''
            ip = self.IP
            for interface in netifaces.interfaces():
                addresses = netifaces.ifaddresses(interface)

                # TODO: add support for IPv6
                for address in addresses.get(socket.AF_INET, []):
                    if ip == address.get('addr'):
                        return address.get('netmask')
        # end SUBNET
    # end __network

    class __disk:
        @property
        def READ_COUNT(self): return psutil.disk_io_counters().read_count
        @property
        def WRITE_COUNT(self): return psutil.disk_io_counters().read_count
        @property
        def READ(self): return psutil.disk_io_counters().read_bytes / 1024 / 1024
        @property
        def WRITE(self): return psutil.disk_io_counters().write_bytes / 1024 / 1024
        @property
        def READ_TIME(self): return psutil.disk_io_counters().read_time
        @property
        def WRITE_TIME(self): return psutil.disk_io_counters().write_time
    # end __disk

    class __partitions:
        @property
        def MOUNTS(self): return [m.mountpoint for m in psutil.disk_partitions()]
        def usage(self, path): return psutil.disk_usage(path)
    # end __partitions

    class __cpu:
        @property
        def USER(self): return psutil.cpu_times().user
        @property
        def SYSTEM(self): return psutil.cpu_times().system
        @property
        def IDLE(self): return psutil.cpu_times().idle
    # end __cpu

    # bound internal classes
    net = __network()
    disk = __disk()
    partitions = __partitions()
    cpu = __cpu()

    @property
    def RAM(self):
        '''returns the amount of free ram'''
        return int(psutil.phymem_usage().free / 1024 / 1024)
    # end RAM

    @property
    def SWAP(self):
        '''returns the amount of free swap'''
        return int(psutil.virtmem_usage().free / 1024 / 1024)
    # end SWAP

    @property
    def LOAD(self):
        '''returns the current average system load'''
        return psutil.cpu_percent()
    # end LOAD
# end __Localhost


class Enum(object):
    '''
    Simple class which converts arguments to class attributes with
    an assigned number.

    :param args:
        string arguments which will create instance
        attributes

    :param integer start:
        keyword argument which controls the start of the sequence

    :param string name:
        the name to provide when str(<enum instance>) is called

    :exception TypeError:
        raised if a value in the incoming arguments is not a string

    :exception KeyError:
        raised if the value provided to get() or __getitem__ is neither
        a string provided as an argument to __init__ or an integer
        which was mapped to an argument
    '''
    def __init__(self, *args, **kwargs):
        self._start = kwargs.get('start', 0)
        self._end = self._start+len(args)
        self.__mappings = {}
        self.__range = xrange(*(self._start, self._end, 1))

        # establish name to use when __repr__ is called
        name = kwargs.get('name') or self.__class__.__name__
        self.__name = name.upper()

        index = 0
        for arg in args:
            if not isinstance(arg, str):
                raise TypeError("%s is not a string" % str(arg))

            index_value = self.__range[index]

            # provide both a string mapping and an integer
            # mapping for use with __getitem__ and get()
            self.__mappings[index_value] = arg
            self.__mappings[arg] = index_value

            # set the attribute on the class
            setattr(self, arg, index_value)

            index += 1

    # external methods
    def __repr__(self): return self.__name
    def __getitem__(self, item): return self.__mappings[item]
    def get(self, item): return self.__getitem__(item)
# end Enum


Localhost = __Localhost()
Software = Enum("MAYA", "HOUDINI", "VRAY", "NUKE", "BLENDER")
State = Enum(
    "PAUSED", "BLOCKED", "QUEUED", "ASSIGN",
    "RUNNING", "DONE", "FAILED"
)

# python datatypes for type comparison
LIST_TYPES = (list, tuple, set)
BOOLEAN_TYPES = (True, False)
STRING_TYPES = (str, unicode, String)
ACTIVE_JOB_STATES = (State.QUEUED, State.RUNNING)

# defaults when creating host
DEFAULT_GROUPS = ['*']
DEFAULT_SOFTWARE = ['*']
DEFAULT_JOBTYPES = ['*']
