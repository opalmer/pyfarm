'''
HOMEPAGE: www.pyfarm.net
INITIAL: August 12 2010
PURPOSE: Primary means of local and remote network identification.  Other
methods can be found in libraries such as lib.system.Info.  This module is
imported in __init_ and can be used via lib.net.<function> if you import lib.net
first.

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

from PyQt4 import QtCore

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import system

LOCAL_ADDRESSES = ('127.0.0.1', '0.0.0.0') 

class ServerFault(Exception):
    '''Raised when a service experiences a serious error'''
    def __init__(self, value):
        self.value = value

    def __str__(self):
        return repr(self.value)
    

class AddressEntry(object):
    '''
    Holds properties related to a network interface such as ip, broadcast,
    and netmask.
    
    @param addressEntry: Source object containing the address entry
    @type  addressEntry: QtNetwork.QNetworkAddressEntry
    '''
    def __init__(self, addressEntry):
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
            self.ip         = self._toIPv4(self._ip)
            self.netmask    = self._toIPv4(self._netmask)
            self.broadcast  = self._toIPv4(self._broadcast)
        
    def __repr__(self):
        return self.ip or ''
        
    def _toIPv4(self, entry):
        '''Convert a long integer to an ip address'''
        ip = entry.toIPv4Address()
        return socket.inet_ntoa(struct.pack('!L', ip))


class NetworkInterface(object):
    '''
    Receives information from and creates properties around a 
    network interface
    
    @param interface: The interface to create the properties from
    @type  interface: QtNetwork.QNetworkInterface
    '''
    def __init__(self, interface):
        self.data      = interface
        self.name      = str(self.data.name())
        self.humanName = str(self.data.humanReadableName())
        self.mac       = str(self.data.hardwareAddress())
        self.address   = self._addresses()
        self.isLocal   = self._isLocal()
        self.isValid   = self._isValid()
        
    def __repr__(self):
        return self.humanName
    
    # TODO: Verify that the last address entry is ALWAYS valid
    def _addresses(self):
        '''Find and return all addresses as a class'''
        addresses = [AddressEntry(None)]
        
        for addr in self.data.addressEntries():
            entry = AddressEntry(addr)
            addresses.append(entry)
            
        return addresses[-1]

    def _isLocal(self):
        '''Return true of the interface is for the localhost'''
        if self.address.ip in LOCAL_ADDRESSES:
            return True
        return False

    def _isValid(self):
        '''
        Evaluate the interface object and return true if the interface contains
        valid information and a non-local address.
        '''
        validData    = self.data.isValid()
        localAddress = False
        
        if not validData or not self.address.ip or self.isLocal:
            return False
        
        return True

def interfaces():
    '''Returns a list of interface objects'''
    for interface in QtNetwork.QNetworkInterface.allInterfaces():
        iFace = NetworkInterface(interface)
        if iFace.isValid:
            yield iFace


def getPort():
    '''Return an open port to use'''
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(('localhost', 0))
    addr, port = s.getsockname()
    s.close()
    return port

def isOpenPort(openPort):
    '''Return an open port to use'''
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.bind(('localhost', openPort))
        addr, port = s.getsockname()
        s.close()
        return True
    except:
        return False

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

def dataStream():
    '''Return the proper data stream to use for tcp servers'''
    major = system.Info.Qt.VERSION_MAJOR
    minor = system.Info.Qt.VERSION_MINOR
    return eval("QtCore.QDataStream.Qt_%i_%i" % (major, minor))