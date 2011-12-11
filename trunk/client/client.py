#!/usr/bin/env python
#
# INITIAL: Nov 13 2011
# PURPOSE: To run commands and manage host information and resources
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
import socket
import logging

from lib import loghandler, preferences, job, host

from twisted.internet import reactor
from twisted.web import resource, xmlrpc, server
from twisted.python import log

RESTART = False
CWD = os.getcwd()
PID = os.getpid()
PORT = preferences.PORT
HOSTNAME = socket.gethostname()
ADDRESS = socket.gethostbyname(HOSTNAME)
MASTER = ()

class Client(xmlrpc.XMLRPC):
    '''
    Main xmlrpc service which controls the client.  Most methods
    are handled entirely outside of this class for the purposes of
    seperation of service and logic.
    '''
    def __init__(self):
        resource.Resource.__init__(self)
        self.allowNone = True
        self.useDateTime = True

        # setup sub handlers
        self.host = host.HostServices()
        self.job = job.Manager()

        self.subHandlers = {
            "host": self.host,
            "job" : self.job
        }
    # end __init__

    def xmlrpc_ping(self):
        '''
        Simply return True.  This call should be used to query
        if a connection can be opened to the server.
        '''
        return True
    # end xmlrpc_ping

    # TODO: stop running jobs on shutdown
    def xmlrpc_shutdown(self):
        '''shutdown the client and reactor'''
        reactor.callLater(0.5, reactor.stop)

        if self.job.job_count:
            log.msg(
                "reactor shutting down with jobs still running!",
                logLevel=logging.WARNING
            )
        # end xmlrpc_shutdown

    def xmlrpc_online(self, state=None):
        return self.job.xmlrpc_online(state)
    # end xmlrpc_online

    def xmlrpc_free(self):
        return self.job.xmlrpc_free()
    # end xmlrpc_free

    def xmlrpc_restart(self):
        '''restart the client'''
        global RESTART
        RESTART = True
        self.xmlrpc_shutdown()
    # end xmlrpc_restart
# end Client

# setup and run the client/reactor
client = Client()
reactor.listenTCP(PORT, server.Site(client))
log.msg("running client at http://%s:%i" % (HOSTNAME, PORT))
reactor.run()

# If RESTART has been set to True then restart the client
# script.  This must be done after the reactor and has been
# shutdown and after we have given the port(s) a chance
# to release.
if RESTART:
    pause = preferences.RESTART_WAIT
    log.msg("preparing to restart the client, pausing %i seconds" % pause)
    time.sleep(pause)
    args = sys.argv[:]

    args.insert(0, sys.executable)
    if sys.platform == 'win32':
        args = ['"%s"' % arg for arg in args]

    os.chdir(CWD)
    os.execv(sys.executable, args)
