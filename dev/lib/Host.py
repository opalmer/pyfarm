'''
HOMEPAGE: www.pyfarm.net
INITIAL: Nov 21 2010
PURPOSE [FOR DEVELOPMENT PURPOSES ONLY]:
    Read and return host information.

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
import socket
import linecache

def hostname():
    return socket.hostname

def ip():
    raise NotImplementedError('Cannot return ip, not implemented')