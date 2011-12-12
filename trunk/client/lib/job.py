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
        '''
        convert a string to a uuid.UUID object

        :exception xmlrpc.Fault(6):
            raised if the uid we are trying to convert to a uuid.UUID object
            cannot be converted
        '''
        if not isinstance(uid, uuid.UUID):
            try:
                uid = uuid.UUID(uid)

            except ValueError:
                # if we fail to convert from a string to a uuid.UUID
                # object be sure we raise an error about it
                raise xmlrpc.Fault(6, "failed to convert '%s' to a uuid" % uid)

        return uid
    # end __uuid

    def __job(self, uid):
        '''
        returns a job object if it exists

        :exception xmlrpc.Fault(5):
            raised if the uuid could not be found in the jobs dictionary
        '''
        uuid = self.__uuid(uid)

        if uuid not in self.jobs:
            raise xmlrpc.Fault(5, "no jobs exist for %s" % uuid)

        return self.jobs[uuid]
    # end __job

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

    def xmlrpc_run(self, command, arguments, environ=None, force=False):
        '''setup and return instances of the job object'''
        # client must be online in order to submit jobs
        if not self.__online:
            raise xmlrpc.Fault(4, '%s is offline' % socket.getfqdn())

        # log a warning if we are over the max job count
        if not force and not self.xmlrpc_free():
            args = (self.job_count, self.job_count_max)
            raise xmlrpc.Fault(2, 'client already running %i/%i jobs' % args)

        # create, manages, and returns information about a job
        job = Job(self, command, arguments, environ)
        self.jobs[job.uuid] = job
        return str(job.uuid)
    # end xmlrpc_run

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

    def xmlrpc_log(self, uid, split=True):
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
        job = self.__job(uid)
        log = open(job.log.name, 'r')
        data = log.read()

        if split:
            return data.split(os.linesep)

        return data
    # end xmlrpc_log

    def xmlrpc_kill(self, uid):
        '''Kills a running process'''
        job = self.__job(uid)
        return job.kill()
    # end xmlrpc_kill

    def xmlrpc_running(self, job=None):
        '''return a list of all running jobs'''
        if job == None:
            jobs = []

            for uid, job in self.jobs.items():
                if job.running:
                    jobs.append(str(job.uuid))

            return jobs

        job = self.__job(job)
        return job.running
    # end xmlrpc_running

    def xmlrpc_elapsed(self, job):
        '''return the total elapsed time for a job (in seconds)'''
        job = self.__job(job)
        return job.elapsed
    # end xmlrpc_elapsed

    def xmlrpc_exit_code(self, uid):
        '''
        return the exit code for the given job (or None
        if it has not been set yet
        '''
        job = self.__job(uid)
        return job.exit_code
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
        self.exit_code = None
        self.end = None
        self.running = False
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
            "Command" : " ".join(self.arguments),
            "Id" : self.uuid,
            "Log Opened" : loghandler.timestamp(),
            "Hostname" : socket.getfqdn(socket.gethostname())
        }
        self.log = loghandler.openLog(self.uuid, **self.environ)
        loghandler.writeHeader(self.log, header)

        log.msg("creating job instance %s" % self.uuid)
        log.msg("...program: %s" % self.command)
        log.msg("...command: %s" % self.arguments)
        log.msg("...log: %s" % self.log.name)

        # setup the process, attach a deferred handler to self.exit
        self.running = True
        self.manager.job_count += 1
        self.process = process.TwistedProcess(
                            self.uuid, self.log,
                            self.command, self.arguments,
                            self.environ
                       )
        reactor.spawnProcess(self.process, *self.process.args)
        self.process.deferred.addCallback(self.exit)
    # end __init__

    @property
    def elapsed(self):
        '''returns either the current amount of time elapsed or the final time'''
        return self._elapsed or time.time() - self.start
    # end elapsed

    def kill(self):
        '''
        Send a kill signal to the process if it is running.  If the process
        is not currently running return False otherwise return True
        '''
        if not self.running:
            return False

        self.exit_code = 1
        self.process.transport.signalProcess('KILL')
        return True
    # end signal

    def exit(self, data):
        '''exit handler called when the process exits'''
        # update some state information
        self.running = False
        self.end = time.time()
        self._elapsed = self.end - self.start
        self.manager.job_count -= 1

        # only set the exit code if it has not already been set
        if self.exit_code == None:
            self.exit_code = data['exit']

        # write a footer and close the log handler
        footer = {
            "Log Closed" : loghandler.timestamp(),
            "exit" : self.exit_code
        }
        loghandler.writeFooter(self.log, footer)
        self.log.close()
    # end exit
# end Job
