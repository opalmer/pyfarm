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
import time
import psutil
import socket
import netifaces

from sqlalchemy import types as sqltypes

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

class _LocalhostConstructor:
    '''namespaced class for storing information about the local host'''

    # constants which do not change at runtime
    OS = OperatingSystem.get()
    OSNAME = OperatingSystem.MAPPINGS.get(OS)
    CPU_COUNT = psutil.NUM_CPUS
    TOTAL_RAM = int(psutil.TOTAL_PHYMEM / 1024 / 1024)
    TOTAL_SWAP = int(psutil.total_virtmem() / 1024 / 1024)

    @staticmethod
    def notimplemented(name):
        msg = "this version of psutil does not implement %s(), " % name
        msg += "please consider upgrading"
        raise NotImplementedError(msg)
    # end notimplemented

    class __network:
        if  hasattr(psutil, 'network_io_counters'):
            SENT = property(lambda self: psutil.network_io_counters().bytes_sent / 1024 / 1024)
            RECV = property(lambda self: psutil.network_io_counters().bytes_recv / 1024 / 1024)
        else:
            SENT = property(lambda self: _LocalhostConstructor.notimplemented('network_io_counters'))
            RECV = property(lambda self: _LocalhostConstructor.notimplemented('network_io_counters'))

        HOSTNAME = property(lambda self: socket.gethostname())
        FQDN = property(lambda self: socket.getfqdn(self.HOSTNAME))

        @property
        def IP(self):
            '''
            returns the best guess ip address for the current host using
            a combination netifaces loops and dns information
            '''
            for ifacename in netifaces.interfaces():
                interface = netifaces.ifaddresses(ifacename)

                for address in interface.get(socket.AF_INET, []):
                    # only eval results that contain an address
                    # and are not considered local
                    if 'addr' in address and not address['addr'].startswith("127"):
                        # Attempt to retrieve the correct ip address
                        # by matching the hostname against the resolved
                        # hostname and alias list.  This of course throw
                        # a socket.herror but that's a different issue
                        addr = address['addr']
                        name, aliaslist, addresslist = socket.gethostbyaddr(addr)
                        if name == self.HOSTNAME or self.HOSTNAME in aliaslist:
                            return addr

            raise socket.herror("failed to retrieve ip for '%s'" % self.HOSTNAME)
        # end IP

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
        if hasattr(psutil, 'disk_io_counters'):
            READ = property(lambda self: psutil.disk_io_counters().read_bytes / 1024 / 1024)
            READ_TIME = property(lambda self: psutil.disk_io_counters().read_time)
            READ_COUNT = property(lambda self: psutil.disk_io_counters().read_count)
            WRITE = property(lambda self: psutil.disk_io_counters().write_bytes / 1024 / 1024)
            WRITE_TIME = property(lambda self: psutil.disk_io_counters().write_time)
            WRITE_COUNT = property(lambda self: psutil.disk_io_counters().read_count)
        else:
            READ = property(lambda self: _LocalhostConstructor.notimplemented('disk_io_counters'))
            READ_TIME = property(lambda self: _LocalhostConstructor.notimplemented('disk_io_counters'))
            READ_COUNT = property(lambda self: _LocalhostConstructor.notimplemented('disk_io_counters'))
            WRITE = property(lambda self: _LocalhostConstructor.notimplemented('disk_io_counters'))
            WRITE_TIME = property(lambda self: _LocalhostConstructor.notimplemented('disk_io_counters'))
            WRITE_COUNT = property(lambda self: _LocalhostConstructor.notimplemented('disk_io_counters'))
    # end __disk

    class __partitions:
        if not hasattr(psutil, 'disk_partitions'):
            MOUNTS = property(lambda self: _LocalhostConstructor.notimplemented('disk_partitions'))
        else:
            MOUNTS = property(lambda self: [m.mountpoint for m in psutil.disk_partitions()])

        def usage(self, path):
            if not hasattr(psutil, 'disk_usage'):
                _LocalhostConstructor.notimplemented('disk_usage')

            return psutil.disk_usage(path)
        # end usage

    # end __partitions

    class __cpu:
        load_loop_count = 2
        load_loop_sleep = .1

        USER = property(lambda self: psutil.cpu_times().user)
        SYSTEM = property(lambda self: psutil.cpu_times().system)
        IDLE = property(lambda self: psutil.cpu_times().idle)
        #LOAD = property(lambda self: psutil.cpu_percent())
        #LOAD = property(lambda self: psutil.cpu_percent())

        @property
        def LOAD(self):
            '''
            calculates the load of the cpu over a shot period of
            time as specified by load_loop_count and load_loop_sleep
            '''
            times = []

            for i in xrange(self.load_loop_count):
                times.append(psutil.cpu_percent())
                time.sleep(self.load_loop_sleep)

            return sum(times) / self.load_loop_count
        # end LOAD
    # end __cpu


    if hasattr(psutil, 'phymem_usage'):
        RAM = property(lambda self: int(psutil.phymem_usage().free / 1024 / 1024))
        SWAP = property(lambda self: int(psutil.virtmem_usage().free / 1024 / 1024))
    else:
        RAM = property(lambda self: self.TOTAL_RAM - (psutil.used_phymem() / 1024 / 1024))
        SWAP = property(lambda self: self.TOTAL_SWAP - (psutil.used_virtmem() / 1024 / 1024))

    # bound internal classes
    net = __network()
    disk = __disk()
    partitions = __partitions()
    cpu = __cpu()
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


Localhost = _LocalhostConstructor()
Software = Enum("MAYA", "HOUDINI", "VRAY", "NUKE", "BLENDER")
State = Enum(
    "PAUSED", "BLOCKED", "QUEUED", "ASSIGN",
    "RUNNING", "DONE", "FAILED"
)

# python datatypes for type comparison
LIST_TYPES = (list, tuple, set)
BOOLEAN_TYPES = (True, False)
STRING_TYPES = (str, unicode, sqltypes.String)
ACTIVE_JOB_STATES = (State.QUEUED, State.RUNNING)

# defaults when creating host
DEFAULT_GROUPS = ['*']
DEFAULT_SOFTWARE = ['*']
DEFAULT_JOBTYPES = ['*']
SQL_TYPES = {
    sqltypes.Integer : (int, ),
    sqltypes.String : (str, unicode),
    sqltypes.Float : (float, ),
    sqltypes.PickleType : (int, float, str, unicode, list, tuple, set)
}
