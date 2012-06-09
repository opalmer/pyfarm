#!/usr/bin/env python
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

'''
receieve, process, and handle job requests from the master
'''

from __future__ import with_statement

import os
import sys
import time
import socket
import logging
import xmlrpclib

# parse command line arguments (before we setup logging)
from pyfarm import logger, cmdargs, lock, datatypes, prefs
from pyfarm.db import tables
from pyfarm.db.query import hosts
from pyfarm.net import rpc as _rpc, dns

options, args = cmdargs.parser.parse_args()
from pyfarm.server import callbacks

from twisted.internet import reactor, threads
from twisted.web import server as _server
from twisted.python import log

CWD = os.getcwd()
HOSTNAME = socket.gethostname()
ADDRESS = dns.ip(HOSTNAME)
SERVICE = None
SERVICE_LOG = None

# PYFARM_RESTART should not start out in the environment
if 'PYFARM_RESTART' in os.environ:
    del os.environ['PYFARM_RESTART']

# setup preferences
prefs.set('database.setup.close-connections', False)

class Server(_rpc.Service, logger.LoggingBaseClass):
    '''
    Main server class to act as an external interface to the
    data base and job server.
    '''
    def __init__(self, log_stream):
        _rpc.Service.__init__(self, log_stream)
    # end __init__

    def xmlrpc_addHost(self, hostname, host_data, force=False):
        '''
        adds a host url and sets up the host in the database
        '''
        host = "%s:%i" % (hostname, prefs.get('network.ports.client'))
        if not force and host in hosts.hostlist(online=None):
            self.log("already added host %s" % host)
            return
    # end xmlrpc_addHost

    def xmlrpc_requestWork(self, hostname):
        '''run when a remote client requests work from the master'''
        assign_work = callbacks.AssignWorkToClient(hostname)
        deferred = threads.deferToThread(assign_work)
        deferred.addCallback(assign_work.sendWork)
        deferred.addErrback(assign_work.rejectRequest)
    # end xmlrpc_requestWork
# end Server

# create a lock for the process so we can't run two servers
# from the same host at once
with lock.ProcessLock(
    'server', kill=options.force_kill, wait=options.wait,
    remove=options.remove_lock
):
    # determine the location we should log to
    if not options.log:
        root = prefs.get('filesystem.locations.general')
        SERVICE_LOG = os.path.join(root, 'server-%s.log' % HOSTNAME)
    else:
        SERVICE_LOG = os.path.abspath(options.log)

    # add an observer for the service log
    observer = logger.Observer(SERVICE_LOG)
    observer.start()

    server = Server(SERVICE_LOG)
    SERVICE = server

    # setup the required tables
    tables.init()

    # start the reactor
    reactor.listenTCP(prefs.get('network.ports.server'), _server.Site(server))
    args = (HOSTNAME, prefs.get('network.ports.server'))
    log.msg(
        "running server at http://%s:%i" % args,
        system="Server", level=logging.INFO
    )
    reactor.run()

# If RESTART has been set to True then restart the server
# script.  This must be done after the reactor and has been
# shutdown and after we have given the port(s) a chance
# to release.
if os.getenv('PYFARM_RESTART') == "true":
    pause = prefs.get('network.rpc.delay')
    log.msg(
        "preparing to restart the server, pausing %i seconds" % pause,
        system="Server"
    )
    time.sleep(pause)
    args = sys.argv[:]

    args.insert(0, sys.executable)

    if datatypes.OS == datatypes.OperatingSystem.WINDOWS:
        args = ['"%s"' % arg for arg in args]

    os.chdir(CWD)
    os.execv(sys.executable, args)
