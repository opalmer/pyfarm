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
receive, process, and handle job requests from the master
'''

import os
import sys
import time
import logging

from pyfarm.server import cmdargs

# Handle command line input before importing anything else.  This
# ensures that if -h or --help is requested or if we have trouble
# parsing the arguments we can run the appropriate action before
# we setup other modules.
options = cmdargs.parser.parse_args()

from pyfarm import logger, lock
from pyfarm.preferences import prefs
from pyfarm.db import tables
from pyfarm.server import rpcqueue
from pyfarm.net import rpc as _rpc
from pyfarm.datatypes.network import HOSTNAME
from pyfarm.datatypes.system import OS, OperatingSystem
from pyfarm.server import callbacks

from twisted.internet import reactor, threads
from twisted.web import server as _server
from twisted.python import log

CWD = os.getcwd()
SERVICE = None
SERVICE_LOG = None

# log the options being produced by the option parser
cmdargs.printOptions(options, log)

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
        self.queue = rpcqueue.Queue(self)
        self.subHandlers = {
            "queue" : self.queue
        }
    # end __init__

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
    reactor.listenTCP(options.port, _server.Site(server))
    args = (HOSTNAME, options.port)
    log.msg(
        "running server at http://%s:%i" % args,
        system="Server", level=logging.INFO
    )
    raise Exception("stopping server, see TODOs in notes")
    reactor.run()

# If RESTART has been set to True then restart the server
# script.  This must be done after the reactor and has been
# shutdown and after we have given the port(s) a chance
# to release.
if os.environ.get('PYFARM_RESTART') == "true":
    pause = prefs.get('network.rpc.delay')
    log.msg(
        "preparing to restart the server, pausing %i seconds" % pause,
        system="Server"
    )
    time.sleep(pause)
    args = sys.argv[:]

    args.insert(0, sys.executable)

    if OS == OperatingSystem.WINDOWS:
        args = ['"%s"' % arg for arg in args]

    os.chdir(CWD)
    os.execv(sys.executable, args)
