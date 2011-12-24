#!/usr/bin/env python
#
# INITIAL: Dec 18 2011
# PURPOSE: Receive, process, and handle job requests for PyFarm
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

from lib import db, preferences

from twisted.internet import reactor
from twisted.web import resource, xmlrpc, server
from twisted.python import log

CWD = os.getcwd()
PID = os.getpid()
PORT = preferences.PORT

class Server(xmlrpc.XMLRPC):
    '''
    Main server class to act as an external interface to the
    data base and job server.
    '''
    def __init__(self):
        resource.Resource.__init__(self)
        self.allowNone = True
        self.useDateTime = True

        # setup sub handlers
    # end __init__
# end Server

if __name__ == '__main__':
    db.tables.init()