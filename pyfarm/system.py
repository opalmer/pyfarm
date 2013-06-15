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
import time
import socket
import getpass
import tempfile
from warnings import warn

try:
    import pwd
    _user = lambda: pwd.getpwuid(os.getuid())[0]

except ImportError:
    import getpass
    _user = lambda: getpass.getuser()

try:
    import multiprocessing
except ImportError:
    multiprocessing = None

import psutil
import ipaddress
import netifaces

from pyfarm.warning import NotImplementedWarning, NetworkWarning
from pyfarm.ext.config.core.loader import Loader
from pyfarm.ext.config.enum import OperatingSystem as _OperatingSystem
from pyfarm.utility import convert


def user():
    """
    Returns the current user name.  On posix based platforms this uses
    :func:`pwd.getpwuid` and on windows it falls back to
    :func:`getpass.getuser`.
    """
    return _user()


class OperatingSystemInfo(object):
    """
    .. note::
        This class has already been instanced onto
        `pyfarm.system.operating_system`

    Namespace class which returns information about
    the current operating system such as case-sensitivity, type, etc.
    """
    _enum = _OperatingSystem()
    OS = _enum.get()
    IS_LINUX = OS == _enum.LINUX
    IS_WINDOWS = OS == _enum.WINDOWS
    IS_MAC = OS == _enum.MAC
    IS_OTHER = OS == _enum.OTHER
    IS_POSIX = OS in (_enum.LINUX, _enum.MAC)
    CASE_SENSITIVE = None

    def __init__(self):
        if self.__class__.CASE_SENSITIVE is None:
            fid, path = tempfile.mkstemp()
            exists = map(os.path.isfile, [path, path.lower(), path.upper()])
            if not any(exists):
                raise ValueError(
                    "failed to determine if path was case sensitive")
            elif all(exists):
                self.__class__.CASE_SENSITIVE = False

            elif exists.count(True) == 1:
                self.__class__.CASE_SENSITIVE = True

            try:
                os.remove(path)
            except:
                pass

    def uptime(self):
        """
        Returns the amount of time the system has been running in
        seconds
        """
        return time.time() - psutil.BOOT_TIME


class NetworkInfo(object):
    """
    .. note::
        This class has already been instanced onto `pyfarm.system.network`

    Namespace class which returns information about the network
    adapters and their state information.
    """
    def __init__(self):
        self._cached_ip = None
        self.config = Loader("network.yml")

    def isPublic(self, address):
        """
        Utility method which returns True if the given address is 'public'.
        This simply means that we should be able to use the provided address
        to contact or be contacted by other hosts on the network.
        """
        address = ipaddress.IPv4Address(
            unicode(address) if isinstance(address, str) else address)

        return not any([
            address.is_link_local, address.is_loopback,
            address.is_unspecified])

    @property
    def _iocounter(self):
        """
        Mapping to the internal network io counter class
        """
        interface = self.interface()
        values = psutil.network_io_counters(pernic=True)
        return values[interface]

    def packetsSent(self):
        """
        Returns the total number of packets sent over the network
        interface provided by :meth:`interface`
        """
        return self._iocounter.packets_sent

    def packetsReceived(self):
        """
        Returns the total number of packets received over the network
        interface provided by :meth:`interface`
        """
        return self._iocounter.packets_recv

    def dataSent(self):
        """
        Amount of data sent in megabytes over the network
        interface provided by :meth:`interface`
        """
        return convert.bytetomb(self._iocounter.bytes_sent)

    def dataReceived(self):
        """
        Amount of data received in megabytes over the network
        interface provided by :meth:`interface`
        """
        return convert.bytetomb(self._iocounter.bytes_recv)

    def errorCountIncoming(self):
        """
        Returns the number of packets which we failed
        to receive on the network interface provided by :meth:`interface`
        """
        return self._iocounter.errin

    def errorCountOutgoing(self):
        """
        Returns the number of packets which we failed
        to receive on the network interface provided by :meth:`interface`
        """
        return self._iocounter.errout

    def hostname(self, fqdn=True):
        """
        Returns the hostname of this machine.  If `fqdn` is True then
        return the fully qualified hostname
        """
        if fqdn:
            return socket.getfqdn()
        else:
            return socket.gethostname()

    def addresses(self):
        """Returns a list of all non-local ip addresses."""
        output = []
        for interface in self.interfaces():
            addrinfo = netifaces.ifaddresses(interface)
            for address in addrinfo.get(socket.AF_INET, []):
                if "addr" in address and self.isPublic(address["addr"]):
                    output.append(address["addr"])

        assert output, "failed to find any ipv4 addresses"
        return output

    def interfaces(self):
        """Returns the names of all valid network interface names"""
        names = []
        for name in netifaces.interfaces():
            # only add network interfaces which have IPv4
            addresses = netifaces.ifaddresses(name)
            if (
                socket.AF_INET in addresses
                and any(addr.get("addr") for addr in addresses[socket.AF_INET])
            ):
                names.append(name)

        assert names, "failed to find any network interface names"
        return names

    def interface(self):
        """
        Based on the result from :meth:`ip` return the network interface
        in use
        """
        public_address = self.ip()
        for interface in netifaces.interfaces():
            addresses = netifaces.ifaddresses(interface).get(socket.AF_INET, [])
            for address in addresses:
                if address.get("addr") == public_address:
                    return interface

        raise ValueError("could not determine network interface")

    def dnsip(self, fqdn=True):
        """
        Returns the IP addresses that the hostname of this
        machine resolves to.

        .. warning::
            This will return None if the dns name maps to a non-public
            address

        >>> if not network.isPublic(socket.gethostbyname(socket.getfqdn())):
        ...     assert network.dnsip() is None
        ... else:
        ...     assert network.dnsip()
        """
        hostname = self.hostname(fqdn=fqdn)
        addr = socket.gethostbyname(hostname)
        reverse_name, aliases, addrs = socket.gethostbyaddr(addr)

        # addr may not be public and is often the case on linux
        # where the hostname is mapped to 127.0.0.1
        if reverse_name == "localhost":
            return
        elif self.isPublic(addr):
            return addr


    def ip(self):
        """
        Attempts to retrieve the ip address for use on the network.  This
        method attempts several ways of finding the correct ip address:
            * use a GET request to the master's REST api
            * ask DNS using the fully qualified domain name
            * bind to an external server and check for the address used (does
              not send any data)
        """
        if self._cached_ip is not None:
            return self._cached_ip

        warn("ip check via REST request to master", NotImplementedWarning)
        checks = [self.dnsip]

        if self.config.get("remote_address_check.enable"):
            checks.extend(self.config.get("remote_address_check.addresses"))

        address = None
        for check in checks:
            if callable(check):
                address = check()
                if address is not None:
                    break

            elif isinstance(check, basestring):
                sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
                try:
                    sock.connect((check, 0))
                except socket.error:
                    warn("failed to check to %s for check", NetworkWarning)
                else:
                    address, port = sock.getsockname()
                    if self.isPublic(address):
                        break
                finally:
                    sock.close()
        else:
            warn("failed to determine ip address", NetworkWarning)
            return None

        self._cached_ip = address

        # before we return the value check to see which
        # network io counter has the highest value since
        # it's the most likely to be the one we're
        # communicating with
        counts = []
        count_mapping = {}
        for nic, counter in psutil.network_io_counters(pernic=True).iteritems():
            count = counter.packets_sent + counter.packets_recv
            counts.append(count)
            count_mapping[count] = nic

        # get the max count then determine if the network
        # interface with the max count is the one we've found
        max_count = max(counts)
        if count_mapping[max_count] != self.interface():
            warn("interface selected may not be the primary interface",
                 NetworkWarning)

        return address


class ProcessorInfo(object):
    """
    .. note::
        This class has already been instanced onto `pyfarm.system.processor`

    Namespace class which returns information about the processor(s)
    in use on the system.

    :attr CPU_COUNT:
        Returns the total number of cpus installed.  This first
        attempts to use :func:`multiprocessing.cpu_count` before
        falling back onto `psutil.NUM_CPUS`
    """
    if multiprocessing is not None:
        try:
            CPU_COUNT = multiprocessing.cpu_count()
        except NotImplementedError, e:
            warn("failed to use multiprocess.cpu_count: %s" % e,
                 RuntimeWarning)

    CPU_COUNT = psutil.NUM_CPUS

    def load(self, iterval=1):
        """
        Returns the load across all cpus value from zero to one.  A value
        of 1.0 means the average load across all cpus is 100%.
        """
        return psutil.cpu_percent(iterval) / self.CPU_COUNT

    def userTime(self):
        """
        Returns the amount of time spent by the cpu in user
        space
        """
        return psutil.cpu_times().user

    def systemTime(self):
        """
        Returns the amount of time spent by the cpu in system
        space
        """
        return psutil.cpu_times().system

    def idleTime(self):
        """
        Returns the amount of time spent by the cpu in idle
        space
        """
        return psutil.cpu_times().idle

    def iowait(self):
        """
        Returns the amount of time spent by the cpu waiting
        on io

        .. note::
            on platforms other than linux this will return None

        # >>> if OS not in (OperatingSystem.LINUX, OperatingSystem.MAC):
        # ...     assert processor.iowait() is None
        # ... else:
        # ...     assert processor.iowait() >= psutil.cpu_times().iowait
        #
        """
        if operating_system.IS_POSIX:
            return psutil.cpu_times().iowait


class MemoryInfo(object):
    """
    .. note::
        This class has already been instanced onto `pyfarm.system.memory`

    Namespace class which returns information about both physical and
    virtual memory on the system.  Unless otherwise noted all units are in
    megabytes.

    This class is a wrapper around methods present on :mod:`psutil` and is
    normally accessed using the instance found on `memory`:

    :attr TOTAL_RAM:
        Total physical memory (ram) installed on the system

    :attr TOTAL_SWAP:
        Total virtual memory (swap) installed on the system
    """
    TOTAL_RAM = convert.bytetomb(psutil.TOTAL_PHYMEM)
    TOTAL_SWAP = convert.bytetomb(psutil.swap_memory().total)

    def swapUsed(self):
        """Amount of swap currently in use"""
        return convert.bytetomb(psutil.swap_memory().used)

    def swapFree(self):
        """Amount of swap currently free"""
        return convert.bytetomb(psutil.swap_memory().free)

    def ramUsed(self):
        """Amount of swap currently free"""
        return convert.bytetomb(psutil.virtual_memory().used)

    def ramFree(self):
        """Amount of ram currently free"""
        return convert.bytetomb(psutil.virtual_memory().available)


# instances of info objects for external use
memory = MemoryInfo()
processor = ProcessorInfo()
network = NetworkInfo()
operating_system = OperatingSystemInfo()