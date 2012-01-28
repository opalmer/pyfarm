#!/usr/bin/env python
#
# INITIAL: Dec 18 2011
# PURPOSE: Receive, process, and handle job requests for PyFarm
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
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
import socket
import logging
import xmlrpclib

# setup and configure logging first
import common.loghandler
SERVICE_LOG = common.loghandler.startLogging('server')

import common.rpc
from server import db, preferences
from common import multicast, lock, cmdoptions

from twisted.internet import reactor
from twisted.web import server as _server
from twisted.web import xmlrpc
from twisted.python import log

CWD = os.getcwd()
HOSTNAME = socket.gethostname()
ADDRESS = socket.gethostbyname(HOSTNAME)
SERVICE = None

# PYFARM_RESTART should not start out in the environment
if 'PYFARM_RESTART' in os.environ:
    del os.environ['PYFARM_RESTART']

class Server(common.rpc.Service):
    '''
    Main server class to act as an external interface to the
    data base and job server.
    '''
    def __init__(self, log_stream):
        common.rpc.Service.__init__(self, log_stream)
        self.hosts = set()
    # end __init__

    def resetClientMasters(self):
        '''
        Informs all clients to reset their master servers to ().  By
        this point the event loop should have terminated but we
        make sure to handle either case.
        '''
        for host in self.hosts:
            if not reactor.running:
                rpc = xmlrpclib.ServerProxy('http://%s' % host, allow_none=True)
                if rpc.setMaster('', True):
                    log.msg("reset master on %s" % host)

                else:
                    log.msg(
                        "%s failed to reset master" % host,
                        logLevel=logging.ERROR
                    )
            else:
                rpc = common.rpc.Connection(host)
                rpc.call('setMaster', '', True)
                log.msg("called setMaster on %s" % host)
    # end resetClientMasters

    def xmlrpc_addHost(self, hostname, host_data, force=False):
        '''
        adds a host url and sets up the
        host in the database
        '''
        host = "%s:%i" % (hostname, preferences.CLIENT_PORT)
        if not force and host in self.hosts:
            log.msg("already added host %s" % host)
            return

        log.msg("adding host %s" % host)
        self.hosts.add(host)

        dbdata =  db.utility.hostToTableData(host_data)
        insert = db.tables.hosts.insert()
        insert.execute(dbdata)
    # end xmlrpc_addHost
# end Server


def incoming_host(data):
    '''
    callback for multicast server, runs whenever a new client
    contacts the server
    '''
    hostname, force = data
    log.msg("incoming heartbeat from %s" % hostname)
    rpc = common.rpc.Connection(hostname, preferences.CLIENT_PORT)
    rpc.call('setMaster', HOSTNAME)
# end incoming_host

# parse command line arguments
options, args = cmdoptions.parser.parse_args()

# create a lock for the process so we can't run two clients
# at once
with lock.ProcessLock('server', kill=options.force_kill, wait=options.wait) \
    as context:
    server = Server(SERVICE_LOG)
    SERVICE = server

    # add exit actions to lock's exit context
    context.addExitAction(server.resetClientMasters)

    # multicast server which listens for new clients
    heartbeat_server = multicast.HeartbeatServer()
    heartbeat_server.addCallback(incoming_host)

    # setup and run the server/reactor
    db.tables.init()

    # bind services
    reactor.listenTCP(preferences.SERVER_PORT, _server.Site(server))
    reactor.listenMulticast(
        preferences.HEARTBEAT_SERVER_PORT, heartbeat_server
    )

    # start reactor and start client discovery
    args = (HOSTNAME, preferences.SERVER_PORT)
    log.msg("running server at http://%s:%i" % args)
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


