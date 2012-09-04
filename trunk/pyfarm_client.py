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
run commands and manage host information and resources
'''

import os
import sys
import time
import logging

from pyfarm.client import cmdargs

# Handle command line input before importing anything else.  This
# ensures that if -h or --help is requested or if we have trouble
# parsing the arguments we can run the appropriate action before
# we setup other modules.
options = cmdargs.parser.parse_args()


from pyfarm.client import system, process, job, master
from pyfarm import lock, datatypes
from pyfarm.db import insert, modify, query

# setup the main log
from pyfarm import logger, prefs
from pyfarm.net import rpc as _rpc
from pyfarm.datatypes import Localhost

from twisted.internet import reactor
from twisted.web import xmlrpc
from twisted.web import server as _server
from twisted.python import log

CWD = os.getcwd()
HOSTNAME = Localhost.net.FQDN
ADDRESS = Localhost.net.IP
MASTER = ()
SERVICE = None
SERVICE_LOG = None

# log the options being produced by the option parser
cmdargs.printOptions(options, log)

# PYFARM_RESTART should not start out in the environment
if 'PYFARM_RESTART' in os.environ:
    del os.environ['PYFARM_RESTART']

class Client(_rpc.Service, logger.LoggingBaseClass):
    '''
    Main xmlrpc service which controls the client.  Most methods
    are handled entirely outside of this class for the purposes of
    separation of service and logic.
    '''
    # provides a location to store our call to reactor.callLater
    def __init__(self, log_stream):
        _rpc.Service.__init__(self, log_stream)

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

    def xmlrpc_master(self):
        '''returns the current master'''
        return MASTER
    # end xmlrpc_master

    def xmlrpc_insertHost(self, drop=True):
        '''inserts a host into the database and returns the host id'''
        try:
            return insert.host(drop=drop)

        except NameError:
            raise xmlrpc.Fault(
                10, "%s already exists in the database" % HOSTNAME
            )
    # end xmlrpc_insertHost

    def xmlrpc_setMaster(self, master, force=False):
        '''
        sets the master server address

        :param boolean force:
            if provided then the value in master will
        '''
        global MASTER

        # if a master address is not provided assume
        # that we are trying to set the master to ()
        if master:
            master = (master, prefs.get('network.ports.server'))
        else:
            master = ()

        # if new address is the current address do nothing
        if master == MASTER:
            self.log("master is already set to %s" % str(master))
            return False

        # if master is already set and force is not use do nothing
        if MASTER and not force:
            self.log("master is already set, use force to override")
            return False

        MASTER = master
        self.log("master set to %s" % str(master))

        if MASTER:
            self.log("sending host information to %s" % str(MASTER))
            # generate information about this system and send it to
            # the master

            hostinfo_sources = {
                "system" : self.sys,
                "network" : self.net
            }

            hostinfo = system.report(hostinfo_sources)

            # update host info with options arguments
            hostinfo['options'] = {
                "host_groups" : options.host_groups,
                "software" : options.host_software
            }

            rpc = _rpc.Connection(MASTER[0], MASTER[1])
            rpc.call('addHost', HOSTNAME, hostinfo, force)

            # if we are using sqlite inform master to update our resource
            # in the database
            if prefs.get('database.engine') == "sqlite":
                self.log("informing master to update our resources")
                rpc.call('resources', HOSTNAME, True)

            # otherwise we update resource information ourselves
            else:
                self.log("updating our own resource information")
                self.xmlrpc_resources('', True)

        return True
    # end xmlrpc_setMaster

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
        if state in datatypes.BOOLEAN_TYPES:
            self.job.online = state
            self.log("client online state set to %s" % str(state))

        elif state is not None:
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
        if count is 0:
            raise xmlrpc.Fault(8, "cannot set jobs_max to zero")

        if not isinstance(count, int) and not count == None:
            raise xmlrpc.Fault(3, "%s is not an integer" % str(count))

        elif isinstance(count, int):
            self.job.job_count_max = count

        # if the current job_count_max is greater
        # than the processor count then send a warning to the console
        if self.job.job_count_max > process.CPU_COUNT:
            args = (self.job.job_count_max, process.CPU_COUNT)
            self.log(
                "max job count (%i) is greater than the cpu count (%i)!" % args,
                level=logging.WARNING
            )

        # if the job count is unlimited show a warning
        elif self.job.job_count_max == -1:
            self.log("max job count is unlimited!", level=logging.WARNING)

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


# create a lock for the process so we can't run two clients
# from the same host at once
with lock.ProcessLock(
    'client', kill=options.force_kill, wait=options.wait,
    remove=options.remove_lock
):
    # determine the location we should log to
    if not options.log:
        root = prefs.get('filesystem.locations.general')
        SERVICE_LOG = os.path.join(root, 'client-%s.log' % HOSTNAME)
    else:
        SERVICE_LOG = os.path.abspath(options.log)

    # add an observer for the service log
    observer = logger.Observer(SERVICE_LOG)
    observer.start()

    # construct keyword arguments used to update the host in
    # the database
    host_table_keywords = {
        "ram_total" : options.ram,
        "cpu_count" : options.cpus,
        "online" : options.online,
        "groups" : options.groups,
        "software" : options.software
    }

    if options.set_master and options.master:
        host_table_keywords['master'] = options.master

    elif options.set_master and options.master is None:
        log.msg(
            "master will be set to None for %s" % HOSTNAME,
            level=logging.WARNING
        )
        host_table_keywords['master'] = options.master

    if query.hosts.exists():
        log.msg("updating %s in the host table" % HOSTNAME)
        modify.hosts.host(
            HOSTNAME,
            **host_table_keywords
        )

    else:
        log.msg("inserting %s into the host table" % HOSTNAME, level="INFO")
        insert.hosts.host(**host_table_keywords)

    MASTER = master.get(options.master)
    client = Client(SERVICE_LOG)
    SERVICE = client

    # start listening for connections with the client
    reactor.listenTCP(prefs.get('network.ports.client'), _server.Site(client))

    # start reactor
    args = (HOSTNAME, prefs.get('network.ports.client'))
    log.msg(
        "running client at http://%s:%i" % args,
        level=logging.INFO, system="Client"
    )
    reactor.run()

# If RESTART has been set to True then restart the client
# script.  This must be done after the reactor and has been
# shutdown and after we have given the port(s) a chance
# to release.
if os.environ.get('PYFARM_RESTART') == 'true' and prefs.get('network.rpc.restart'):
    pause = prefs.get('network.rpc.delay')
    log.msg(
        "preparing to restart the client, pausing %i seconds" % pause,
        system="Client"
    )
    time.sleep(pause)
    args = sys.argv[:]

    args.insert(0, sys.executable)
    if Localhost.OS == datatypes.OperatingSystem.WINDOWS:
        args = ['"%s"' % arg for arg in args]

    os.chdir(CWD)
    os.execv(sys.executable, args)
