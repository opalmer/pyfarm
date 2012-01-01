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
import sys
import time
import site
import socket
import xmlrpclib

from lib import db, preferences

cwd = os.path.abspath(os.path.dirname(__file__))
root = os.path.abspath(os.path.join(cwd, ".."))
site.addsitedir(root)

import common.rpc
from common import loghandler, multicast

from twisted.internet import reactor
from twisted.web import resource, xmlrpc
from twisted.web import server as _server
from twisted.python import log

CWD = os.getcwd()
PID = os.getpid()
HOSTNAME = socket.gethostname()
ADDRESS = socket.gethostbyname(HOSTNAME)

class Server(common.rpc.Service):
    '''
    Main server class to act as an external interface to the
    data base and job server.
    '''
    def __init__(self):
        common.rpc.Service.__init__(self)
        self.hosts = set()
    # end __init__

    # TODO: test to ensure this works with multiple clients
    def xmlrpc_multicast(self):
        '''
        sends a multicast packet and adds any results
        to self.clients
        '''
        multicast.send()
    # end xmlrpc_multicast

    def xmlrpc_addHost(self, host):
        '''
        adds a host url
        '''
        if host not in self.hosts:
            log.msg("adding host %s" % host)

        self.hosts.add(host)
        common.rpc.ping(host)
    # end xmlrpc_addHost
# end Server


# setup and run the server/reactor
db.tables.init()
server = Server()
reactor.listenTCP(preferences.SERVER_PORT, _server.Site(server))
log.msg("running server at http://%s:%i" % (HOSTNAME, preferences.SERVER_PORT))
reactor.run()

# If RESTART has been set to True then restart the server
# script.  This must be done after the reactor and has been
# shutdown and after we have given the port(s) a chance
# to release.
if os.getenv('PYFARM_RESTART') == "true":
    pause = preferences.RESTART_DELAY
    log.msg("preparing to restart the server, pausing %i seconds" % pause)
    time.sleep(pause)
    args = sys.argv[:]

    args.insert(0, sys.executable)

    if sys.platform == 'win32' or os.name == 'nt':
        args = ['"%s"' % arg for arg in args]

    os.chdir(CWD)
    os.execv(sys.executable, args)
