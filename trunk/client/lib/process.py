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

from twisted.internet import protocol, reactor, defer

# TODO: handle output of the deferred object so the main client knows that 
#       we have finished processing
class TwistedProcess(protocol.ProcessProtocol):
    '''
    Create a Twisted process object
    
    :param string command: The command to run
    '''
    def __init__(self, command):
        self.command = command
        self.env = copy.deepcopy(os.environ)
        self.deferred = defer.Deferred()
        
        # construct the command list and arguments to pass 
        # to reactor.spawnProcess
        self.commandList = command.split()
        self.commandList[0] = self.__findProgram(self.commandList[0])
        self.args = (self.commandList[0], self.commandList, self.env)
    # END __init__
    
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
            
        raise OSError('command not found: %s' % name)
    # END __findProgram
    
    def connectionMade(self):        
        self.transport.write(self.command)        
        self.transport.closeStdin()
    # END connectionMade
    
    def outReceived(self, data):
        print "stdout: %s" % data.strip()
    # END outReceived
    
    def errReceived(self, data):
        print "stderr: %s" % data
    # END errReceived
    
    def processEnded(self, status):
        code = status.value.exitCode
        print "exit %s" % code
        
        if code != 0:
            self.deferred.errback(code)
        else:
            self.deferred.callback(self)
    # END processEnded
# END TwistedProcess

# XXX: output may need to be updated to handle TODO note on TwistedProcess
def runcmd(command):
    process = TwistedProcess(command)
    reactor.spawnProcess(process, *process.args)
    return process.deferred
# END runcmd