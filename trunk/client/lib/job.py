# No shebang line, this module is meant to be imported
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

from __future__ import with_statement

import os
import time
import copy
import uuid
import types
import socket
import logging

from twisted.internet import reactor
from twisted.python import log
from twisted.web import xmlrpc, resource

import process
import loghandler
import preferences

MASTER = ()

class Manager(xmlrpc.XMLRPC):
    '''
    Manages running or terminated jobs including starting, stopping,
    state queries, and log handling.

    .. note:
        This class should only be used using it's module instance (Manager),
        reloading or accessing this class directly will break job management
        for the client.
    '''
    def __init__(self):
        resource.Resource.__init__(self)
        self.__online = True
        self.jobs = {}
        self.job_count = 0
        self.job_count_max = preferences.MAX_JOBS
        log.msg("job manager initialized")
    # end __init__

    def __uuid(self, uid):
        '''convert a string to a uuid.UUID object'''
        if not isinstance(uid, uuid.UUID):
            uid = uuid.UUID(uid)

        return uid
    # end __uuid

    def __precheck(self):
        # if the current job_count_max is greater
        # than the processor count then send a warning to the console
        if self.job_count_max > process.CPU_COUNT:
            args = (self.job_count_max, process.CPU_COUNT)
            log.msg(
                "max job count (%i) is greater than the cpu count (%i)!" % args,
                logLevel=logging.WARNING
            )

        # if the job count is unlimited show a warning
        elif self.job_count_max == -1:
            log.msg(
                "max job count is unlimited!",
                logLevel=logging.WARNING
            )
    # end __precheck

    def xmlrpc_run(self, command, arguments, environ=None):
        '''setup and return instances of the job object'''
        if not self.__online:
            raise xmlrpc.Fault(4, '%s is offline' % socket.getfqdn())

        job = Job(self, command, arguments, environ)
        return str(job.uuid)
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

    # end newJob

    def job(self, uid):
        '''Retrieve a job and return its instance'''
        uid = self.__uuid(uid)
        return self.jobs(uid)
    # end job

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
            self.__online = state
            log.msg("client online state set to %s" % str(state))

        elif not isinstance(state, types.NoneType):
            raise xmlrpc.Fault(3, "%s is not a valid state" % str(state))

        return self.__online
    # end online

    def xmlrpc_jobs_max(self, count=None):
        '''
        Much like online() this method will return self.jobs_max unless
        a value for count is provided.

        :param integer count:
            the new value to set self.max_jobs to

        :exception xmlrpc.Fault(3):
            raised if the new count is not an integer
        '''
        if isinstance(count, types.IntType):
            self.job_count_max = count

        elif not isinstance(count, types.NoneType):
            raise xmlrpc.Fault(3, "%s is not an integer" % str(count))

        self.__precheck()

        return self.job_count_max
    # end jobs_max

    def xmlrpc_free(self):
        '''
        returns True if there is additional space for processing or if
        the max number of jobs is unlimited (-1)
        '''
        if self.job_count_max == -1 or self.job_count < self.job_count_max:
            return True

        return False
    # end free

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
# end Manager


class Job(object):
    '''
    Maintains, controls, and sets up a job.  This class should always
    setup and instanced by _Manager to maintain the state of the client.

    :param Manager manager:
        the manager class we're we will modify the running job count, state,
         etc.

    :param string command:
        the command to run

    :param string or list arguments:
        the arguments to provide to the command

    :param dictionary environ:
        values to update the environment with
    '''
    def __init__(self, manager, command, arguments, environ=None):
        # copy of the original arguments
        self.__command = command
        self.__arguments = arguments

        # state information
        self.uuid = uuid.uuid1()
        self.manager = manager
        self.start = time.time()
        self.end = None
        self._elapsed = None # caches the 'final' elapsed time

        # setup the arguments and commands
        self.command = process.which(command)
        self.arguments = [command]
        if isinstance(arguments, types.StringTypes):
            self.arguments.extend(arguments.split())

        # create a copy of the original environment and
        # update it with custom entries if they are provided
        self.environ = copy.deepcopy(os.environ)
        if isinstance(environ, dict):
            self.environ.update(environ)

        # setup logfile for process
        header = {
            "command" : self.command,
            "arguments" : arguments,
            "uuid" :self.uuid
        }
        self.log = loghandler.openLog(self.uuid, **header)

        log.msg("Creating Job Instance %s" % self.uuid)
        log.msg("...command: %s" % self.command)
        log.msg("...arguments: %s" % self.arguments)
        log.msg("...log: %s" % self.log.name)

        self.process = process.TwistedProcess(
                            self.command, self.arguments,
                            environ, self.log
                       )
        reactor.spawnProcess(self.process, *self.process.args)
        self.process.deferred.addCallback(self.exit)
    # end __init__

    @property
    def elapsed(self):
        '''returns either the current amount of time elapsed or the final time'''
        return self._elapsed or time.time() - self.start
    # end elapsed

    def exit(self, data):
        '''exit handler called when the process exits'''
        self.end = time.time()
        self._elapsed = self.end - self.start
    # end exit
# end Job
