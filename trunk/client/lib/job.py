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
import itertools

from twisted.python import log

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

    def newJob(self, command, environ=None):
        '''setup and return instances of the job object'''
        job = Job(command, environ=environ)

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
    def __init__(self, command, environ=None):
        self.__command = command
        self.command = self.__command.split()

        # create a copy of the original environment and
        # update it with custom entries if they are provided
        self.environ = copy.deepcopy(os.environ)
        if isinstance(environ, dict):
            self.environ.update(environ)
    # end __init__

    # TODO: fully test, streamline and improve efficiency
    @staticmethod
    def which(program, paths=None, names=None):
        '''
        Searches the system path, preferences.PATHS, and paths for the full
        path to the location of the requested program.  Normally the operating
        system would resolve the path for us however the twisted framework must
        be provided the full path

        :param list paths:
            additional paths to search for the program

        :param list names:
            list of additional names that the program could possibly be called

        :exception OSError:
            raised if we fail to find the program

        :exception TypeError:
            raised if the paths or names argument is not a list
        '''
        if paths == None:
            paths = []

        # ensure the paths argument is a list
        if not isinstance(paths, list):
            raise TypeError("paths expects a list not a %s" % type(paths))

        if names == None:
            names = []

        # ensure the names argument is a list
        if not isinstance(paths, list):
            raise TypeError("names expects a list not a %s" % type(paths))

        # extend the paths list to include the preferences and system
        # paths
        paths = []
        envvars = set(['PATH'])

        # construct a list of environment variables to search for paths in
        for envvar in preferences.PATHS_ENV:
            envvars.add(envvar)

        # iterate over all environment variables and retrieve
        # any additional paths
        for envvar in envvars:
            for path in os.getenv(envvar, '').split(os.pathsep):
                paths.append(path)

        # extend the search paths by those listed in the preferences
        paths.extend(preferences.PATHS_LIST)

        # construct a list of possible program names.  Depending on
        # the operating system this could mean we need to 'expand'
        # upon possible names
        programs = [program]
        if os.name == "nt":
            products = itertools.product(
                        [program, program.upper()],
                        ['.exe', '.EXE', '']
                      )

            for name, extension in products:
                entry = "%s%s" % (name, extension)
                if entry not in programs:
                    programs.append(entry)

        # extend the program names with the list of custom names
        programs.extend(names)

        # finally before searching for the program remove any duplicate
        # paths or paths that do not exist
        system_paths = []
        for path in paths:
            if os.path.isdir(path) and path not in system_paths:
                system_paths.append(path)

        for root, tail in itertools.product(system_paths, programs):
            path = os.path.join(root, tail)
            if os.path.isfile(path):
                return path
    # end which
# end Job
