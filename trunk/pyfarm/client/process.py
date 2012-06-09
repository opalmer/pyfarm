# No shebang line, this module is meant to be imported
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
creates and manages independent processes
'''

import os
import UserDict
import itertools
from uuid import UUID

from twisted.web import xmlrpc
from twisted.internet import protocol, defer
from twisted.python import log

class TwistedProcess(protocol.ProcessProtocol):
    '''
    Create a Twisted process object

    :param string command: The command to run

    :exception xmlrpc.Fault(7):
        raised if a uid was provided but not a uid
    '''
    # !!!
    # TODO: determine if setting the path to the cwd adversely impacts the
    #        process in an unexpected way
    # !!!
    def __init__(self, uuid, log, command, arguments, environ,
                 path=os.getcwd(), uid=None, gid=None):
        # ensure the uuid object is the proper type
        if not isinstance(uuid, UUID):
            raise TypeError("expected UUID type for uuid import argument")

        self.uuid = uuid
        self.log = log
        self.command = command
        self.arguments = arguments
        self.deferred = defer.Deferred()

        # copy the current environment and then update it
        # with any custom information
        self.environ = dict(os.environ)
        if isinstance(environ, (dict, UserDict.UserDict)):
            self.environ.update(environ)

        elif environ is not None:
            raise TypeError("expected None, dict, or UserDict for environ")

        # construct the command list and arguments to pass
        # to reactor.spawnProcess
        self.args = [self.command, self.arguments, self.environ]

        if uid and not gid:
            raise xmlrpc.Fault(7, "you must provide both uid and gid")

        if uid:
            self.args.append(uid)
            self.args.append(gid)
    # end __init__

    def connectionMade(self):
        '''write the command to standard input'''
        self.transport.write(self.command)
        self.transport.closeStdin()
    # end connectionMade

    def outReceived(self, data):
        '''output received on sys.stdout'''
        self.log.write(data.strip())
    # end outReceived

    def errReceived(self, data):
        '''output received on sys.stderr'''
        self.log.write(data.strip())
    # end errReceived

    def processEnded(self, status):
        '''Called when the process exist and returns the proper callback'''
        data = {
                    "exit" : status.value.exitCode,
                    "command" : self.command
               }

        # call deferred
        log.msg("%s %s" % (self.uuid, status.value.message))
        self.deferred.callback(data)
    # end processEnded
# end TwistedProcess


# TODO: fully test, streamline and improve efficiency
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
    # simply return the absolute absolute file path
    # if it exists
    if os.path.isfile(program):
        return os.path.abspath(program)

    if paths is None:
        paths = []

    # ensure the paths argument is a list
    if not isinstance(paths, list):
        raise TypeError("paths expects a list not a %s" % type(paths))

    if names is None:
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
                    ['.exe', '.EXE', '.cmd', '.CMD', '.bat', '.BAT', '']
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


    raise xmlrpc.Fault(1, "failed to find %s in %s" % (program, system_paths))
# end which
