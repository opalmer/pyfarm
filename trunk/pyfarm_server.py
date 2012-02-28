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

from __future__ import with_statement

import os
import sys
import time
import pprint
import socket
import logging
import xmlrpclib

# parse command line arguments (before we setup logging)
from common import cmdargs
options, args = cmdargs.parser.parse_args()

# setup the main log
import common.logger
SERVICE_LOG = common.logger.startLogging('server')

import common.rpc
from common.db import tables
from server import db, preferences
from common import multicast, lock
from common.db import hosts

from twisted.internet import reactor
from twisted.web import server as _server
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
        for host in hosts.hostlist(online=True):
            port = preferences.CLIENT_PORT
            if not self.xmlrpc_ping(host, int(port)):
                log.msg("%s does not appear to be up, skipping" % host)
                continue

            if not reactor.running:
                url = "http://%s:%i" % (host, port)
                rpc = xmlrpclib.ServerProxy(url, allow_none=True)
                if rpc.setMaster('', True):
                    log.msg("reset master on %s" % host)

                else:
                    log.msg(
                        "%s failed to reset master" % host,
                        logLevel=logging.ERROR
                    )
            else:
                rpc = common.rpc.Connection(host, port)
                rpc.call('setMaster', '', True)
                log.msg("called setMaster on %s" % host)
    # end resetClientMasters

    def xmlrpc_addHost(self, hostname, host_data, force=False):
        '''
        adds a host url and sets up the host in the database
        '''
        host = "%s:%i" % (hostname, preferences.CLIENT_PORT)
        if not force and host in self.hosts:
            log.msg("already added host %s" % host)
            return
    # end xmlrpc_addHost
# end Server


def incoming_host(data):
    '''
    callback for multicast server, runs whenever a new client
    contacts the server
    '''
    def success(*args):
        if args[0]:
            log.msg("successfully set master on %s" % hostname)
        else:
            log.msg("failed to set master on %s" % hostname)
    # end success

    hostname, force = data
    log.msg("incoming heartbeat from %s" % hostname)

    # run setMaster on the incoming client
    rpc = common.rpc.Connection(hostname, preferences.CLIENT_PORT, success)
    rpc.call('setMaster', HOSTNAME)
# end incoming_host

# create a lock for the process so we can't run two clients
# at once
with lock.ProcessLock('server', kill=options.force_kill, wait=options.wait) \
    as context:
    server = Server(SERVICE_LOG)
    SERVICE = server

    # add exit actions to lock's exit context
    context.addExitAction(server.resetClientMasters)

    # setup multicast server to listen for incoming clients
    heartbeat_server = multicast.HeartbeatServer()
    heartbeat_server.addCallback(incoming_host)
    reactor.listenMulticast(preferences.HEARTBEAT_SERVER_PORT, heartbeat_server)

    # setup the required tables
    tables.init()

    # start the reactor
    reactor.listenTCP(preferences.SERVER_PORT, _server.Site(server))
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
