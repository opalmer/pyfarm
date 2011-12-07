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

from lib import process, loghandler, preferences
from lib import job

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

        :param boolean state:
            the new online state to set

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

    def xmlrpc_run(self, command, arguments, environ=None, force=False):
        '''
        Runs the requested command

        :param string command:
            the name of the command to run, this can either be a full path
            or just the name of the program

        :param boolean force:
            If True disregard the current job count and run the command
            anyway

        :param dictionary environ:
            environment to use while running the job

        :exception xmlrpc.Fault(1):
            raised if the given command could not be found

        :exception xmlrpc.Fault(2):
            raised if the client is already running the max
            number of jobs

        :exception xmlrpc.Fault(4):
            raised if the host is currently offline
        '''
        newjob = job.manager.newJob(command, arguments, environ=environ)

        # TODO: refactor to use job manager
#        free = self.xmlrpc_free()
#        args = (Client.JOB_COUNT, Client.JOB_COUNT_MAX)
#
#        # client must be online in order to submit jobs
#        if not self.xmlrpc_online():
#            raise xmlrpc.Fault(4, '%s is offline' % HOSTNAME)
#
#        if not force and not free:
#            raise xmlrpc.Fault(2, 'client already running %i/%i jobs' % args)
#
#        # log a warning if we are over the max job count
#        if force and not free:
#            warn = "overriding max running jobs, current job count %i/%i" % args
#            log.msg(warn, logLevel=logging.WARNING)
#
#        try:
#            host = (HOSTNAME, ADDRESS, PORT)
#            newjob = job.manager.newJob(command, environ=environ)
#
#            processHandler = process.ExitHandler(Client, host, MASTER)
#            processCommand, uuid = process.runcmd(command)
#            processCommand.addCallback(processHandler.exit)
#            return str(uuid)
#
#        except OSError, error:
#            Client.JOB_COUNT -= 1
#            raise xmlrpc.Fault(1, str(error))
    # end xmlrpc_run

    def xmlrpc_log(self, uuidstr, split=True):
        '''
        Returns log file for the given uuid

        :param string uuidstr:
            the uuid to return a log file for

        :param boolean split:
            if split is True then return the log lines as a list

        :exception xmlrpc.Fault(5):
            raised if a log file does not exist for the requested log

        :exception xmlrpc.Fault(6):
            raised if we failed to convert the provided uuidstr
            to a uuid.UUID object
        '''
        try:
            data = loghandler.getLog(uuidstr, stream=split)

            if not split:
                data = str(data)

            return data

        except loghandler.UnknownLog, error:
            raise xmlrpc.Fault(5, error)

        except ValueError:
            raise xmlrpc.Fault(6, "failed to convert '%s' to a uuid" % uuidstr)
    # end xmlrpc_log

    # TODO: stop running jobs on shutdown
    def xmlrpc_shutdown(self):
        '''shutdown the client and reactor'''
        if Client.JOB_COUNT:
            log.msg(
                "reactor shutting down with jobs still running!",
                logLevel=logging.WARNING
            )

        reactor.callLater(0.5, reactor.stop)
    # end xmlrpc_shutdown
# end Client

client = Client()
reactor.listenTCP(PORT, server.Site(client))
log.msg("running client at http://%s:%i" % (HOSTNAME, PORT))
reactor.run()
