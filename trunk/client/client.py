#!/usr/bin/env python
#
# INITIAL: Nov 13 2011
# PURPOSE: To run commands and manage host information and resources
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
import site
import types
import socket
import logging

# add common python package
cwd = os.path.abspath(os.path.dirname(__file__))
root = os.path.abspath(os.path.join(cwd, ".."))
site.addsitedir(root)

import common.rpc
from common import loghandler, multicast
from lib import preferences, job, system, process

from twisted.internet import reactor
from twisted.web import resource, xmlrpc
from twisted.web import server as _server
from twisted.python import log

CWD = os.getcwd()
PID = os.getpid()
HOSTNAME = socket.gethostname()
ADDRESS = socket.gethostbyname(HOSTNAME)
MASTER = ()


class Client(common.rpc.Service):
    '''
    Main xmlrpc service which controls the client.  Most methods
    are handled entirely outside of this class for the purposes of
    separation of service and logic.
    '''
    def __init__(self):
        common.rpc.Service.__init__(self)

        # setup sub handlers
        self.sys = system.SystemInformation()
        self.net = system.NetworkInformation()
        self.job = job.Manager(self)

        self.subHandlers = {
            "sys": self.sys,
            "net" : self.net,
            "job" : self.job
        }
    # end __init__

    def _blockShutdown(self):
        return self.job.xmlrpc_running()
    # end _blockShutdown

    def _blockRestart(self):
        return self._blockShutdown()
    # end _blockRestart

    def xmlrpc_running(self):
        '''returns True if there are jobs marked as running by the manager'''
        if self.job.xmlrpc_running():
            return True
        return False
    # end xmlrpc_running

    def xmlrpc_online(self, state=None):
        '''
        Return True of the client is currently online or set
        the online state if a valid state argument is provided

        :param boolean state:
            the new online state to set

        :exception xmlrpc.Fault(3):
            raised if the new state is not in (True, False)
        '''
        if isinstance(state, types.BooleanType):
            self.job.online = state
            log.msg("client online state set to %s" % str(state))

        elif not isinstance(state, types.NoneType):
            raise xmlrpc.Fault(3, "%s is not a valid state" % str(state))

        return self.job.online
    # end xmlrpc_online

    def xmlrpc_jobs_max(self, count=None):
        '''
        Much like online() this method will return self.jobs_max unless
        a value for count is provided.

        :param integer count:
            the new value to set self.max_jobs to

        :exception xmlrpc.Fault(3):
            raised if the new count is not an integer

        :exception xmlrpc.Fault(8):
            raised if we attempt to set max jobs to 0
        '''
        if count == 0:
            raise xmlrpc.Fault(8, "cannot set jobs_max to zero")

        if not isinstance(count, types.IntType) and not count == None:
            raise xmlrpc.Fault(3, "%s is not an integer" % str(count))

        elif isinstance(count, types.IntType):
            self.job.job_count_max = count

        # if the current job_count_max is greater
        # than the processor count then send a warning to the console
        if self.job.job_count_max > process.CPU_COUNT:
            args = (self.job.job_count_max, process.CPU_COUNT)
            log.msg(
                "max job count (%i) is greater than the cpu count (%i)!" % args,
                logLevel=logging.WARNING
            )

        # if the job count is unlimited show a warning
        elif self.job.job_count_max == -1:
            log.msg(
                "max job count is unlimited!",
                logLevel=logging.WARNING
            )

        return self.job.job_count_max
    # end xmlrpc_jobs_max

    def xmlrpc_free(self):
        '''
        returns True if there is additional space for processing or if
        the max number of jobs is unlimited (-1)
        '''
        max_count = self.job.job_count_max
        if max_count == -1 or self.job.job_count < max_count:
            return True

        return False
    # end xmlrpc_free
# end Client


def setMaster(data):
    '''
    sets the master address which is used to return
    job information back to a central host for processing
    '''
    global MASTER
    MASTER = data

    if MASTER:
        try:
            log.msg("master host set to %s:%i" % MASTER)

        # the above will fail if we Ctrl+C out
        # of the program
        except TypeError:
            pass
# end setMaster

# setup main services
client = Client()
multicast = multicast.Server()
multicast.deferred.addCallback(setMaster)

# bind services
reactor.listenTCP(preferences.CLIENT_PORT, _server.Site(client))
reactor.listenMulticast(preferences.MULTICAST_PORT, multicast)

log.msg("running client at http://%s:%i" % (HOSTNAME, preferences.CLIENT_PORT))
reactor.run()

# If RESTART has been set to True then restart the client
# script.  This must be done after the reactor and has been
# shutdown and after we have given the port(s) a chance
# to release.
if os.getenv('PYFARM_RESTART') == 'true':
    pause = preferences.RESTART_DELAY
    log.msg("preparing to restart the client, pausing %i seconds" % pause)
    time.sleep(pause)
    args = sys.argv[:]

    args.insert(0, sys.executable)
    if sys.platform == 'win32' or os.name == 'nt':
        args = ['"%s"' % arg for arg in args]

    os.chdir(CWD)
    os.execv(sys.executable, args)
