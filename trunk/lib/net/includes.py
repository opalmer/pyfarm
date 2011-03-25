'''
HOMEPAGE: www.pyfarm.net
INITIAL: August 12 2010
PURPOSE: Primary means of local and remote network identification.  Other
         methods can be found in libraries such as lib.system.info.  This module
         is imported in __init_ and can be used via lib.net.<function> if you
         import lib.net first.

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
import os
import sys
import struct
import socket
import fnmatch

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

import lib.net.errors
from lib import system

LOCAL_ADDRESSES = ('127.0.*.*', '0.0.0.0')

class AddressEntry(QtNetwork.QNetworkAddressEntry):
    '''
    Holds properties related to a network interface such as ip, broadcast,
    and netmask.

    @param addressEntry: Source object containing the address entry
    @type  addressEntry: QtNetwork.QNetworkAddressEntry
    '''
    def __init__(self, addressEntry):
        super(AddressEntry, self).__init__(addressEntry)
        self.data       = addressEntry
        if addressEntry == None:
            self._ip        = None
            self._netmask   = None
            self._broadcast = None
            self.ip         = None
            self.netmask    = None
            self.broadcast  = None

        else:
            self.data       = addressEntry
            self._ip        = self.data.ip()
            self._netmask   = self.data.netmask()
            self._broadcast = self.data.broadcast()

            # reprocessed entries
            self.ip         = AddressEntry.toIPv4(self._ip)
            self.netmask    = AddressEntry.toIPv4(self._netmask)
            self.broadcast  = AddressEntry.toIPv4(self._broadcast)

    def __repr__(self):
        return self.ip or ''

    @staticmethod
    def toIPv4(entry):
        '''Convert a long integer to an ip address'''
        ip = entry.toIPv4Address()
        return socket.inet_ntoa(struct.pack('!L', ip))

    @staticmethod
    def isLocal(entry):
        '''Return true if the given entry is a local ip address'''
        for ipFilter in LOCAL_ADDRESSES:
            if fnmatch.fnmatch(entry, ipFilter):
                return True
        return False


class NetworkInterface(QtNetwork.QNetworkInterface):
    '''
    Receives information from and creates properties around a
    network interface

    @param interface: The interface to create the properties from
    @type  interface: QtNetwork.QNetworkInterface
    '''
    def __init__(self, interface):
        super(NetworkInterface, self).__init__(interface)
        self.data      = interface
        self.uid       = str(self.data.name())
        self.name      = str(self.data.humanReadableName())
        self.mac       = str(self.data.hardwareAddress())
        self.addresses = self._addresses()
        self.address   = self._filterAddresses(self.addresses)

        # shortcut attributes that point to the resolved address entry
        if self.address:
            self.ip        = self.address.ip
            self.netmask   = self.address.netmask
            self.broadcast = self.address.broadcast

        else:
            self.ip        = None
            self.netmask   = None
            self.broadcast = None

    def _addresses(self):
        '''Return a list of all network addresses'''
        return [ AddressEntry(addr) for addr in self.data.addressEntries() ]

    def _filterAddresses(self, addresses):
        '''
        Filter the incoming list of address and return only the valid (non-
        local) entry
        '''
        if addresses:
            for addr in addresses:
                if addr and not AddressEntry.isLocal(addr.ip):
                    return addr

    def __repr__(self):
        return self.name


class NetworkInterfaces(QtNetwork.QNetworkInterface):
    '''Functions and operations related to the network interfaces'''
    def __init__(self):
        super(NetworkInterfaces, self).__init__()

    def _validHardwareAddr(self, interface):
        '''
        Return True if the hardware (mac) address is valid

        @param interface: The interface to test the validity of
        @type  interface: QtNetwork.QNetworkInterface
        '''
        mac = str(interface.hardwareAddress()).strip()
        if mac and not mac.startswith("00:00:00"):
            return True
        return False

    def _validAddresses(self, interface):
        '''Ensure that the given interface contains valid addresses'''
        if NetworkInterface(interface).addresses:
            return True
        return False

    def _validInterface(self, interface):
        '''
        Return True if the given interface is valid

        @param interface: The interface to test the validity of
        @type  interface: QtNetwork.QNetworkInterface
        '''
        qtValid   = interface.isValid()
        macValid  = self._validHardwareAddr(interface)
        validAddr = self._validAddresses(interface)

        if qtValid and macValid and validAddr:
            return True

        return False

    @property
    def interfaces(self):
        '''Return a list of all network interfaces'''
        interfaces = []
        for interface in self.allInterfaces():
            if self._validInterface(interface):
                interfaces.append(NetworkInterface(interface))

        return interfaces


def lookupAddress(hostname):
    '''
    Given a hostname return an ip address
    NOTE: Requires DNS
    '''
    return socket.gethostbyname(hostname)

def lookupHostname(ip):
    '''
    Return the hostname for the given ip address
    NOTE: Requires DNS
    '''
    return socket.gethostbyaddr(ip)[0]

def hostname(fqdn=False):
    '''
    Return the proper hostname to use during this python session.

    @param fqdn: If true, return the fully qualified domain name
    @type  fqdn: C{bool}
    '''
    hostname = socket.gethostname()
    fqdn     = socket.getfqdn(hostname)
    ip       = lookupAddress(hostname)

    if lookupHostname(lookupAddress(hostname)) in (hostname, fqdn):
        if fqdn:
            return socket.getfqdn(hostname)
        return hostname

    else:
        raise lib.net.errors.DNSMismatch("Resolved ip does not match hostname")

def interfaces():
    '''Returns a list of interface objects'''
    return NetworkInterfaces().interfaces

def addresses():
    '''Returns all network addresses'''
    return [AddressEntry.toIPv4(intr.toIPv4Address()) for intr in interfaces()]

def interface():
    '''
    Return the best possible interface to use for connecting to the local
    network.  We do this by asking the network which network interface maches
    the current hostname.  If the query fails or does not match we move onto
    the next network adapter.
    '''
    dnsHost = hostname(fqdn=True)
    dnsIp   = lookupAddress(dnsHost)
    for interface in interfaces():
        ip = interface.address.ip
        if ip == dnsIp or not AddressEntry.isLocal(ip):
            return interface

def address(convert=True):
    '''
    Return the QNetworkAddressEntry to use in network servers

    @param convert: If true, convert to a IPv4 string
    @type  convert: C{bool}
    '''
    ip    = INTERFACE.address.data.ip()

    if convert:
        ip = AddressEntry.toIPv4(ip)

    return ip

def hardwareAddress(basic=False):
    '''
    Return the hardware address for the system

    @param basic: If true, return a lowercase colon free version
    @type  basic: C{bool}
    '''
    mac = INTERFACE.mac

    if basic:
        mac = mac.lower().replace(":", "")

    return mac

def getPort():
    '''Return an open port to use'''
    ip = interface().ip
    s  = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind((ip, 0))
    addr, port = s.getsockname()
    s.close()
    return port

def isOpenPort(openPort):
    '''
    Ensure that the given port is available for use and can be bound.

    @param openPort: The port to check
    @type  openPort: C{int}
    '''
    ip = address()
    try:
        s  = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.bind((ip, openPort))
        addr, port = s.getsockname()
        s.close()
        return True

    except:
        return False

def dataStream():
    '''Return the proper data stream to use for tcp servers'''
    major = system.info.Qt.VERSION_MAJOR
    minor = system.info.Qt.VERSION_MINOR
    return eval("QtCore.QDataStream.Qt_%i_%i" % (major, minor))

# establish the base object
INTERFACE = interface()
if __name__ == "__main__":
    iface = INTERFACE
    addr  = address()
    print "%s [%s]" %(iface.name, iface.address.ip)
