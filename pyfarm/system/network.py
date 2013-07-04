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
Returns information about the network including ip address, dns, data
sent/received, and some error information.
"""

from warnings import warn

import netifaces
import socket
import IPy
import psutil

from pyfarm.ext.config.core.loader import Loader
from pyfarm.utility import convert
from pyfarm.warning import NetworkWarning, NotImplementedWarning


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
        return isLocalIPv4Address(address)

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
                if "addr" in address and isLocalIPv4Address(address["addr"]):
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

    def ipFromDNS(self):
        """
        Returns the IP addresses that the hostname of this
        machine resolves to.

        .. warning::
            This will return None if the dns name maps to a non-public
            address
        """
        try:
            hostname = self.hostname(fqdn=True)
            addr = socket.gethostbyname(hostname)

        # The above will sometimes fail if the fqdn hostname
        # is not a name that can be resolved to an ip.  In
        # those cases we try again with the local hostname
        # instead.
        except socket.gaierror:
            hostname = self.hostname(fqdn=False)
            addr = socket.gethostbyname(hostname)

        try:
            reverse_name, aliases, addrs = socket.gethostbyaddr(addr)

        except socket.herror:
            warn("failed to resolve hostname for %s" % addr, NetworkWarning)
            return None

        # addr may not be public and is often the case on linux
        # where the hostname is mapped to 127.0.0.1
        if reverse_name == "localhost":
            return
        elif self.isPublic(addr):
            return addr

    def ipFromMaster(self):
        """Attempts to connect to the master and request our current address"""
        raise NotImplementedError

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
        checks = [self.ipFromDNS]

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
                    warn("failed to check to use %s to check address" % check,
                         NetworkWarning)
                else:
                    address, port = sock.getsockname()
                    if isLocalIPv4Address(address):
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


class IPSet(object):
    """
    Simple class which returns True if an :class:`IPy.IP` object
    is contained within the set.

    >>> addresses = IPSet(IPy.IP("192.168.0.0/16"))
    >>> IPy.IP("192.168.0.1") in addresses
    True
    >>> IPy.IP("0.0.0.0") in addresses
    False
    """
    def __init__(self, *addresses):
        self._addresses = set(addresses)

    def __repr__(self):
        addr_repr = map(repr, [ip.strCompressed(1) for ip in self._addresses])
        return "%s(%s)" % (self.__class__.__name__, ", ".join(addr_repr))

    def __contains__(self, item):
        assert isinstance(item, IPy.IP), "__contains__ requires an IPy.IP item"
        for address in self._addresses:
            if item in address:
                return True
        return False


IP_SPECIAL_USE = IPy.IP("0.0.0.0/8")
IP_LINK_LOCAL = IPy.IP("169.254.0.0/16")
IP_LOOPBACK = IPy.IP("127.0.0.0/8")
IP_MULTICAST = IPy.IP("224.0.0.0/4")
IP_BROADCAST = IPy.IP("255.255.255.255")
IP_PRIVATE = IPSet(
    IPy.IP("10.0.0.0/8"),
    IPy.IP("172.16.0.0/12"),
    IPy.IP("192.168.0.0/16")
)
IP_NONNETWORK = IPSet(
    IP_SPECIAL_USE,
    IP_LINK_LOCAL,
    IP_LOOPBACK,
    IP_MULTICAST,
    IP_BROADCAST
)