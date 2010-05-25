'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 11 2009
PURPOSE: Network utilities that do not fit into a specific category reside here

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
from lib.Logger import Logger
import socket

__MODULE__ = "lib.network.Utils"

def ResolveHost(host):
    '''
    Given IP address or hostname, return hostname and IP

    VARS:
        host (string) - hostname or IP address

    OUTPUT:
        list2 - [hostname, address]
    '''
    output = []
    try:
        output.append(socket.gethostbyaddr(host)[0])
    except (socket.gaierror, socket.herror):
        return "BAD_HOST"

    try:
        output.append(socket.gethostbyaddr(host)[2][0])
    except (socket.gaierror, socket.herror):
        return "BAD_HOST"

    return output

def GetLocalIP(master):
    '''Get the ip address of the local computer'''
    from socket import socket, SOCK_DGRAM, AF_INET
    s = socket(AF_INET, SOCK_DGRAM)
    s.connect((master, 0))
    return s.getsockname()[0]