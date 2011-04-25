'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 25 2011
PURPOSE: Clients resource to manage, query, and check clients

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

from PyQt4 import QtCore

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

import xmlrpc
from lib import logger, system, net

logger = logger.Logger()

class Resource(QtCore.QObject):
    def __init__(self, parent=None):
        super(Resource, self).__init__(parent)
        self.parent = parent

    def newClient(self, hostname, ip, sysinfo):
        '''
        Add a client to the database using the given hostname, ip address,
        and system information
        '''
        client = {
                    "hostname" : hostname,
                    "address"  : ip,
                    "system"   : sysinfo
                 }
        self.emit(QtCore.SIGNAL("newHost"), client)

        logger.netserver("New Host: %s (%s)" % (hostname, ip))

        # reply back to client with success and reason code
        return True, 200