# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

"""
Module which is used to query system level information such as
cpu times, memory, and disk usage.  Inline code included in
this module was produced for the purposes of doctests.
"""

import os
import getpass
import socket
import tempfile
from warnings import warn

try:
    import pwd
except ImportError:
    pwd = None

try:
    import multiprocessing
except ImportError:
    multiprocessing = None

import psutil
import netifaces

from pyfarm.ext.config.enum import OperatingSystem as _OperatingSystem
from pyfarm.ext.utility import user, convert

OperatingSystem = _OperatingSystem()
OS = OperatingSystem.get()
OSNAME = OperatingSystem.get(OS)


def user():
    """
    Returns the current user name.  On posix based platforms this uses
    :func:`pwd.getpwuid` and on windows it falls back to
    :func:`getpass.getuser`.

    >>> assert isinstance(user(), basestring)
    """
    if pwd is not None:
        return pwd.getpwuid(os.getuid())[0]
    else:
        return getpass.getuser()


class FileSystemInfo(object):
    # TODO: property for filesytem case sensitivity
    # TODO:
    pass


class NetworkInfo(object):
    """
    Namespace class which returns information about the network
    adapters and their state information.
    """
    def __init__(self):
        self.__ip = None

    def hostname(self, fqdn=True):
        """
        returns the hostname of this machine

        >>> assert network.hostname() == socket.getfqdn()
        >>> assert network.hostname(fqdn=False) == socket.gethostname()
        """
        if fqdn:
            return socket.getfqdn()
        else:
            return socket.gethostname()

    def interfaces(self):
        """
        returns the names of all valid network interface names

        >>> names = network.interfaces()
        >>> assert all(name in netifaces.interfaces() for name in names)
        >>> assert len(netifaces.interfaces()) >= len(network.interfaces())
        >>> addresses = map(netifaces.ifaddresses, names)
        >>> assert all(socket.AF_INET in i for i in addresses)
        """
        names = []
        for name in netifaces.interfaces():
            if (
                name.startswith("vmnet")
                or name.startswith("br")
                or name.startswith("lo")
            ):
                continue
            else:
                # TODO: need some windows specific names to test

                # only add network interfaces which have IPv4
                # addresses (for now)
                addresses = netifaces.ifaddresses(name)
                if (
                    socket.AF_INET in addresses
                    and any(addr.get("addr") for addr in addresses[socket.AF_INET])
                ):
                    names.append(name)

        return names

    def ip(self):
        """
        Attempts to retrieve the local ip address of this
        computer.
        """
        if self.__ip is not None:
            return self.__ip

        # first attempt to use dns to resolve the ip address
        dnsip = socket.gethostbyname(socket.gethostname())
        if not dnsip.statswith("127."):
            self.__ip = dnsip

        # TODO: try contacting/asking master
        # TODO: try using an external service to the 'bound/bind' address

        return self.__ip


class ProcessorInfo(object):
    """
    Namespace class which returns information about the processor(s)
    in use on the system.
    """
    @property
    def CORES(self):
        """
        Returns the total number of cpus installed.  This first
        attempts to use :func:`multiprocessing.cpu_count` before
        falling back onto `psutil.NUM_CPUS`

        >>> cpu_count = None
        >>> if multiprocessing is not None:
        ...      try:
        ...          cpu_count = multiprocessing.cpu_count()
        ...      except:
        ...          pass
        >>> cpu_count = psutil.NUM_CPUS if cpu_count is None else cpu_count
        >>> assert processor.CORES == cpu_count
        """
        if multiprocessing is not None:
            try:
                return multiprocessing.cpu_count()
            except Exception, e:
                warn("failed to use multiprocess.cpu_count: %s" % e,
                     RuntimeWarning)

        return psutil.NUM_CPUS

    def load(self, iterval=1):
        """
        Returns the load across all cpus value from zero to one.  A value
        of 1.0 means the average load across all cpus is 100%.

        >>> assert psutil.cpu_percent(.5) / processor.CORES > 0
        >>> assert processor.load(.5) > 0
        """
        return psutil.cpu_percent(iterval) / self.CORES

    def user(self):
        """
        Returns the amount of time spent by the cpu in user
        space

        >>> assert psutil.cpu_times().user <= processor.user()
        """
        return psutil.cpu_times().user

    def system(self):
        """
        Returns the amount of time spent by the cpu in system
        space

        >>> assert psutil.cpu_times().system <= processor.system()
        """
        return psutil.cpu_times().system

    def idle(self):
        """
        Returns the amount of time spent by the cpu in idle
        space

        >>> assert psutil.cpu_times().idle <= processor.idle()
        """
        return psutil.cpu_times().idle

    def iowait(self):
        """
        Returns the amount of time spent by the cpu waiting
        on io

        .. note::
            on platforms other than linux this will return None

        >>> if OS not in (OperatingSystem.LINUX, OperatingSystem.MAC):
        ...     assert processor.iowait() is None
        ... else:
        ...     assert processor.iowait() >= psutil.cpu_times().iowait

        """
        if OS not in (OperatingSystem.LINUX, OperatingSystem.MAC):
            return None
        else:
            return psutil.cpu_times().iowait


class MemoryInfo(object):
    """
    Namespace class which returns information about both physical and
    virtual memory on the system.  Unless otherwise noted all units are in
    megabytes.

    This class is a wrapper around methods present on :mod:`psutil` and is
    normally accessed using the instance found on `memory`:

    >>> from pyfarm.ext.system import memory
    """
    @property
    def TOTAL_RAM(self):
        """
        Total physical memory (ram) installed on the system

        >>> assert convert.bytetomb(psutil.TOTAL_PHYMEM) == memory.TOTAL_RAM
        """
        return convert.bytetomb(psutil.TOTAL_PHYMEM)

    @property
    def TOTAL_SWAP(self):
        """
        Total virtual memory (swap) installed on the system

        >>> assert convert.bytetomb(psutil.total_virtmem()) == memory.TOTAL_SWAP
        """
        return convert.bytetomb(psutil.total_virtmem())

    def swapUsed(self):
        """
        Amount of swap currently in use

        >>> assert convert.bytetomb(psutil.swap_memory().used) == memory.swapUsed()
        """
        return convert.bytetomb(psutil.swap_memory().used)

    def swapFree(self):
        """
        Amount of swap currently free

        >>> assert convert.bytetomb(psutil.swap_memory().free) == memory.swapFree()
        """
        return convert.bytetomb(psutil.swap_memory().free)

    def ramUsed(self):
        """
        Amount of swap currently free

        >>> assert convert.bytetomb(psutil.virtual_memory().used) == memory.ramUsed()
        """
        return convert.bytetomb(psutil.virtual_memory().used)

    def ramFree(self):
        """
        Amount of ram currently free

        >>> assert convert.bytetomb(psutil.virtual_memory().available) == memory.ramFree()
        """
        return convert.bytetomb(psutil.virtual_memory().available)


# instances of info objects for external use
memory = MemoryInfo()
processor = ProcessorInfo()
network = NetworkInfo()
filesystem = FileSystemInfo()