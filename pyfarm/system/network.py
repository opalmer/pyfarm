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
from pyfarm.error import NetworkError
from pyfarm.warning import NetworkWarning


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

    @property
    def _iocounter(self):
        """
        Mapping to the internal network io counter class
        """
        interface = self.interface()
        values = psutil.net_io_counters(pernic=True)
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
                addr = address.get("addr")

                if addr is not None:
                    try:
                        ip = IPy.IP(addr)
                    except ValueError:
                        warn(
                            "could not convert %s to a valid IP object" % addr,
                            NetworkWarning)
                    else:
                        if ip in IP_PRIVATE:
                            output.append(addr)

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

    def interface(self, addr=None):
        """
        Based on the result from :meth:`ip` return the network interface
        in use
        """
        addr = self.ip() if addr is None else addr

        for interface in netifaces.interfaces():
            addresses = netifaces.ifaddresses(interface).get(socket.AF_INET, [])
            for address in addresses:
                if address.get("addr") == addr:
                    return interface

        raise ValueError("could not determine network interface")

    def ip(self):
        """
        Attempts to retrieve the ip address for use on the network.
        """
        if self._cached_ip is not None:
            return self._cached_ip

        # get the amount of traffic for each network interface,
        # we use this to help determine if the most active interface
        # is the interface dns provides
        sums = []
        counters = psutil.net_io_counters(pernic=True)
        for address in self.addresses():
            interface = self.interface(address)
            try:
                counter = counters[interface]
            except KeyError:
                bytes_sent, bytes_recv, packets_sent, packets_recv = 0, 0, 0, 0
            else:
                bytes_sent = counter.bytes_sent
                bytes_recv = counter.bytes_recv
                packets_recv = counter.bytes_recv
                packets_sent = counter.bytes_sent

            sums.append((
                address,
                bytes_recv + bytes_sent + packets_sent + packets_recv))


        hostname = self.hostname(fqdn=True)
        try:
            dnsip = socket.gethostbyname(hostname)

            # depending on the system dns implementation
            # socket.gethostbyname might give us a loopback address
            if IPy.IP(dnsip) in IP_LOOPBACK:
                dnsip = None

        except socket.gaierror:
            dnsip = None

        if not sums and dnsip is None:
            raise NetworkError("no ip address found")

        # sort addresses based on how 'acive' they appear
        sums.sort(cmp=lambda a, b: 1 if a[1] > b[1] else -1, reverse=True)

        # if the most active address is not the address
        # that's mapped via dns, print a warning and return
        # the dns address
        if dnsip is not None and sums and sums[0][0] != dnsip:
            warn("DNS address != most active active address",
                 NetworkWarning)

        self._cached_ip = sums[0][0]

        return self._cached_ip


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

if __name__ == "__main__":
    n = NetworkInfo()
    print n.ip()