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
import copy
import uuid
import types

from twisted.python import log

import process
import loghandler
import preferences

class _Manager(object):
    '''
    Manages running or terminated jobs including starting, stopping,
    state queries, and log handling.

    .. note:
        This class should only be used using it's module instance (Manager),
        reloading or accessing this class directly will break job management
        for the client.
    '''
    def __init__(self):
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

    def newJob(self, command, arguments, environ=None):
        '''setup and return instances of the job object'''
        job = Job(command, arguments, environ=environ)
        return job
    # end newJob

    def getJob(self, uid):
        '''Retrieve a job and return its instance'''
        uid = self.__uuid(uid)
    # end getJob
# end _Manager

# instance of job manager class, to be used both inside
# and outside of this module
manager = _Manager()


class Job(object):
    '''
    Maintains, controls, and sets up a job.  This class should always
    setup and instanced by _Manager to maintain the state of the client.
    '''
    def __init__(self, command, arguments, environ=None):
        self.__command = command
        self.__arguments = arguments
        self.command = process.which(command)
        self.uuid = uuid.uuid1()

        # create the argument list, if we are already provided
        # a list then do not attempt to split
        self.arguments = arguments
        if isinstance(arguments, types.StringTypes):
            self.arguments = arguments.split()

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
    # end __init__
# end Job
