'''
HOMEPAGE: www.pyfarm.net
INITIAL: Mar. 14 2011
PURPOSE: Contains exceptions for use by other network libraries

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

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", ".."))
if PYFARM not in sys.path: sys.path.append(PYFARM)

import lib.errors

class ServerFault(lib.errors.BaseException):
    '''Raised when a service experiences a serious error'''
    def __init__(self, value):
        super(ServerFault, self).__init__(value)

class DNSMismatch(lib.errors.BaseException):
    '''Raised when a dns entry does not match a reverse lookup'''
    def __init__(self, value):
        super(DNSMismatch, self).__init__(value)

# cleanup objects
del CWD, PYFARM
