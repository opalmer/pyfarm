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
import socket
import logging

from lib import process, preferences

from twisted.internet import reactor
from twisted.web import resource, xmlrpc, server
from twisted.python import log

PID = os.getpid()
PORT = preferences.PORT
HOSTNAME = socket.gethostname()
ADDRESS = socket.gethostbyname(HOSTNAME)
MASTER = ()

class Client(xmlrpc.XMLRPC):
    '''
    Main xml rpc service.

    *Class Attributes*
        ONLINE - If true the client can accecpt and process new jobs
        JOB_COUNT - Current number of jobs running
        JOB_COUNT_MAX - Maximum number of jobs we are allowed to run
    '''
    ONLINE = True
    JOB_COUNT = 0
    JOB_COUNT_MAX = preferences.MAX_JOBS

    def __init__(self):
        resource.Resource.__init__(self)
        self.allowNone = True
        self.useDateTime = True
    # end __init__

    def xmlrpc_quit(self):
        '''Shutdown the reactor and client'''
        reactor.quit()
    # end xmlrpc_quit

    def xmlrpc_online(self, state=None):
        '''
        Return True of the client is currently online and set
        the online state if a valid state argument is provided

        :exception xmlrpc.Fault(3):
            raised if the new state is not in (True, False)
        '''
        if state != None:
            # ensure the new client state is valid
            if state not in (True, False):
                error = "%s is not a valid client state" % str(state)
                raise xmlrpc.Fault(3, error)

            # set the new client state
            Client.ONLINE = state

        return Client.ONLINE
    # end xmlrpc_online

    def xmlrpc_free(self):
        '''
        Returns True if the client has extra room for additional
        processing.
        '''
        # if max is -1 then always return True
        if Client.JOB_COUNT_MAX == -1:
            return True

        elif Client.JOB_COUNT >= Client.JOB_COUNT_MAX:
            return False

        return True
    # end xmlrpc_acceptJobs

    def xmlrpc_ping(self):
        '''
        Simply return True.  This call should be used to query
        if a connection can be opened to the server.
        '''
        return True
    # end xmlrpc_ping

    def xmlrpc_run(self, command, force=False):
        '''
        Runs the requested command

        :param boolean force:
            If True disregard the current job count and run the command
            anyway

        :exception xmlrpc.Fault(1):
            raised if the given command could not be found

        :exception xmlrpc.Fault(4):
            raised if the host is currently offline
        '''
        free = self.xmlrpc_free()
        args = (Client.JOB_COUNT, Client.JOB_COUNT_MAX)

        # client must be online in order to submit jobs
        if not self.xmlrpc_online():
            raise xmlrpc.Fault(4, '%s is offline' % HOSTNAME)

        if not force and not free:
            fault = 'client already running %i/%i jobs' % args
            raise xmlrpc.Fault(2, fault)

        # log a warning if we are over the max job count
        if force and not free:
            warn = "overriding max running jobs, current job count %i/%i" % args
            log.msg(warn, logLevel=logging.WARNING)

        try:
            host = (HOSTNAME, ADDRESS, PORT)
            processHandler = process.ExitHandler(Client, host, MASTER)
            processCommand = process.runcmd(command)
            processCommand.addCallback(processHandler.exit)

        except OSError, error:
            Client.JOB_COUNT -= 1
            raise xmlrpc.Fault(1, str(error))
    # end xmlrpc_run
# end Client

client = Client()
reactor.listenTCP(PORT, server.Site(client))
log.msg("running client at http://%s:%i" % (HOSTNAME, PORT))
reactor.run()