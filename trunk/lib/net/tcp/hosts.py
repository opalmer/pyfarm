# No shebang line, this module is meant to be imported
#
# INITIAL: April 2008 (revised in August 2010)
# PURPOSE: Administration server to provide vital functions to the client such
#          as proper shutdown procedures, service restarts, etc.
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
import site

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, "..", "..", ".."))
site.addsitedir(root)

import xmlrpc
from lib import logger, db

logger = logger.Logger()

class Resource(xmlrpc.BaseResource):
    def __init__(self, sql, parent):
        super(Resource, self).__init__(sql, parent)
        print self.sql

    def newClient(self, hostname, ip, sysinfo):
        '''
        Add a client to the database using the given hostname, ip address,
        and system information
        '''
        msg = "Adding Host: %s" % hostname
        logger.info(msg)
        self.parent.updateConsole("client", msg, color='green')

        if db.network.addHost(self.sql, hostname, ip, sysinfo):
            return True, 200

        else:
            return False, "Host Already In DB"

    def updateClient(self, hostname, ip, sysinfo):
        '''
        Same procedure as newClient but this is meant to update rather than
        create a host connection
        '''
        return True