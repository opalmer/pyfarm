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

from pyfarm import lock, logger, errors
from pyfarm.client import process, job, master
from pyfarm.db import insert, modify, query
from pyfarm.datatypes.network import FQDN
from pyfarm.datatypes.system import OS, OperatingSystem
from pyfarm.preferences import prefs
from pyfarm.net import rpc as _rpc

from twisted.internet import reactor
from twisted.web import xmlrpc
from twisted.web import server as _server
from twisted.python import log

CWD = os.getcwd()
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
        self.job = job.Manager(self)

        self.subHandlers = {
            "job" : self.job
        }


    # end __init__

    def xmlrpc_master(self):
        '''returns the current master'''
        return MASTER
    # end xmlrpc_master

    def xmlrpc_setMaster(self, new_master, database=False):
        '''
        sets the master server IP

        :param boolean database:
            if provided then override the value --set-master provided on
            the command line
        '''
        global MASTER
        if not isinstance(new_master, (list, tuple)):
            raise xmlrpc.Fault(
                0, "invalid master argument, expected tuple(host, por)"
            )

        log.msg("setting MASTER global to %s" % new_master)
        MASTER = new_master

        if options.store_master or database:
            log.msg("setting master in database")
            modify.host.host(
                FQDN,
                master=new_master[0]
            )
        else:
            log.msg("not setting master in database")
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
        if state in (True, False):
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
        SERVICE_LOG = os.path.join(root, 'client-%s.log' % FQDN)
    else:
        SERVICE_LOG = os.path.abspath(options.log)

    # add an observer for the service log
    observer = logger.Observer(SERVICE_LOG)
    observer.start()

    master_port = query.master.port(options.master)

    # construct keyword arguments used to update the host in
    # the database
    host_table_keywords = {
        "ram_total" : options.ram,
        "cpu_count" : options.cpus,
        "online" : options.online,
        "groups" : options.groups,
        "software" : options.software,
        "port" : options.port
    }

    if options.store_master and options.master:
        host_table_keywords['master'] = options.master

    elif options.store_master and options.master is None:
        log.msg(
            "master will be set to None for %s" % FQDN,
            level=logging.WARNING
        )
        host_table_keywords['master'] = options.master

    if query.hosts.exists():
        log.msg("updating %s in the host table" % FQDN)
        modify.host.host(
            FQDN,
            **host_table_keywords
        )

    else:
        log.msg("inserting %s into the host table" % FQDN, level="INFO")
        insert.host.host(**host_table_keywords)

    # try to get the master port from the database before
    # relying on preferences
#    query.master.port(options.master)


    MASTER = (master.get(options.master), prefs.get('network.ports.server'))
    client = Client(SERVICE_LOG)
    SERVICE = client

    # start listening for connections with the client
    reactor.listenTCP(options.port, _server.Site(client))

    # start reactor
    args = (FQDN, options.port)
    log.msg(
        "running client at http://%s:%i" % args,
        level=logging.INFO, system="Client"
    )
    reactor.run()

    # set this host as offline
    log.msg("setting host as offline in database")
    modify.host.host(FQDN, online=False)

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
    if OS == OperatingSystem.WINDOWS:
        args = ['"%s"' % arg for arg in args]

    os.chdir(CWD)
    os.execv(sys.executable, args)
