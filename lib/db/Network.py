'''
HOMEPAGE: www.pyfarm.net
INITIAL: Dec 28 2010
PURPOSE: To create, edit, and remove network related database entries.

This file is part of PyFarm.
Copyright (C) 2008-2010 Oliver Palmer

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

CWD      = os.path.dirname(os.path.abspath(__file__))
PYFARM   = os.path.abspath(os.path.join(CWD, "..", ".."))
MODULE   = os.path.basename(__file__)
DB_XML   = os.path.join(PYFARM, "cfg", "dbbase.xml")
LOGLEVEL = 2
if PYFARM not in sys.path: sys.path.append(PYFARM)

import includes as db

def addHost(host, ip, mac):
    '''
    Add a host to the database and ensure it
    is only created once.  This function assumes
    the host you are adding is a "new" host.
    '''
    sqlite = db.connect()

    # IF host not in database, add it
    # OTHERWISE, complain!

addHost()