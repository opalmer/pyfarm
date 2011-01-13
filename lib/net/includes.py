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
import socket

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

class ServerFault(Exception):
    '''Raised when a service experiences a serious error'''
    def __init__(self, value):
        self.value = value

    def __str__(self):
        return repr(self.value)


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