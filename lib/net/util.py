'''
HOMEPAGE: www.pyfarm.net
INITIAL: August 12 2010
PURPOSE: Primary means of local and remote network identification.  Other
methods can be found in libraries such as lib.system.Info.  This module is
imported in __init_ and can be used via lib.net.<function> if you import lib.net
first.

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

import socket

def hostname():
    '''Return the hostname in use by the local system'''
    return socket.gethostname()

def ip():
    '''
    Return the ip address of the local system
    NOTE: Requires DNS
    '''
    return lookupAddress(hostname())

def getPort():
    '''Return an open port to use'''
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.bind(('localhost', 0))
    addr, port = s.getsockname()
    s.close()
    return port

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
