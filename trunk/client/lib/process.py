# No shebang line, this module is meant to be imported
#
# INITIAL: Nov 13 2011
# PURPOSE: Used to create and manage a process
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
import copy
import uuid
import itertools
import multiprocessing

import loghandler
import preferences

from twisted.internet import protocol, reactor, defer
from twisted.python import log

CPU_COUNT = multiprocessing.cpu_count()

class ExitHandler(object):
    '''
    Handles the output of a process

    :param Client:
        main client class object that we will use to control
        the number of running jobs

    :param tuple host:
        three part tuple with the hostname, address, and port

    :param tuple master:
        three part tuple with the address to the master to report our exit
        code to
    '''
    def __init__(self, Client, host, master):
        self.Client = Client
        self.host = host # hostname, address, and port of the client
        self.master = master
        self.Client.JOB_COUNT += 1
    # end __init__

    def exit(self, data):
        '''Handle the exit status data of a process'''
        args = (data['command'],data['exit'])
        self.Client.JOB_COUNT -= 1

        if data['exit'] != 0:
            log.msg("command '%s' failed with code %i" % args)

        else:
            log.msg("command '%s' finished with code %i" % args)

    # end exit
# end ExitHandler


class TwistedProcess(protocol.ProcessProtocol):
    '''
    Create a Twisted process object

    :param string command: The command to run
    '''
    def __init__(self, command):
        self.command = command
        self.env = copy.deepcopy(os.environ)
        self.deferred = defer.Deferred()
        self.uuid = uuid.uuid1()
        self.log = loghandler.openLog(
                    self.uuid,
                    Command=self.command,
                   )
        log.msg("attempting to run '%s'" % command)

        # construct the command list and arguments to pass
        # to reactor.spawnProcess
        self.commandList = command.split()
        self.commandList[0] = self.__findProgram(self.commandList[0])
        self.args = (self.commandList[0], self.commandList, self.env)
    # end __init__

    def __findProgram(self, name):
        '''Returns the full path to the requested program or command'''
        # no need to search for the full command path if the a valid
        # file path has already been provided to us
        if os.path.isfile(name):
            return os.path.abspath(name)

        # create a list of possible command names
        if os.name == "nt":
            commands = (
                name, "%s.exe" % name,
                "%s.EXE" % name.upper(), name.upper()
            )

        else:
            commands = (name, )

        # loop over all paths in the system path
        # and attempt to find a file matching the given command name
        for root in os.environ['PATH'].split(os.pathsep):
            for command in commands:
                path = os.path.join(root, command)

                if os.path.isfile(path):
                    return path

        error = OSError("'%s' command not found" % name)
        log.err(_stuff=error, _why='command not found')
        raise error
    # end __findProgram

    def connectionMade(self):
        '''write the command to standard input'''
        self.transport.write(self.command)
        self.transport.closeStdin()
    # end connectionMade

    def outReceived(self, data):
        '''output received on sys.stdout'''
        loghandler.writeLine(self.log, data.strip())
    # end outReceived

    def errReceived(self, data):
        '''output received on sys.stderr'''
        loghandler.writeLine(self.log, data.strip())
    # end errReceived

    def processEnded(self, status):
        '''Called when the process exist and returns the proper callback'''
        code = status.value.exitCode
        log.msg("process exit %i: %s" % (code, self.command))

        data = {
                    "exit" : code,
                    "command" : self.command
               }

        # write footer and call deferred
        loghandler.writeFooter(self.log, **data)
        self.log.close()
        self.deferred.callback(data)
    # end processEnded
# end TwistedProcess

# XXX: output may need to be updated to handle TODO note on TwistedProcess
def runcmd(command):
    process = TwistedProcess(command)
    reactor.spawnProcess(process, *process.args)
    return process.deferred, process.uuid
# end runcmd


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
# end which