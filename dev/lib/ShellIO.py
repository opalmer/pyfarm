'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 10 2011
PURPOSE [FOR DEVELOPMENT PURPOSES ONLY]:
    To process information to and from the shell, network, and other 
    input/output devices.

    This file is part of PyFarm.
    Copyright (C) 2008-2011 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import subprocess
import threading

def runCommand(command, env=os.environ, thread=False):
    '''Run a single command and yield the results'''
    process = subprocess.Popen(
                                    command,
                                    stdout=subprocess.PIPE,
                                    shell=True
                               )
    
    output, errors = process.communicate()
    return process.returncode, output, errors

class CommandThreads(object):
    '''
    Small command queue to hold, ran commands, their state, logs, exit codes, 
    etc.  So long as the original execution window remains open this object 
    and its Python session will persist in the background.
    '''
    def __init__(self):
        self.data = {}
        
    def _run(self, command):
        '''
        Run the process and assign its output to the appropriate
        dictionary entries
        '''
        process = subprocess.Popen(
                                        command,
                                        stdout=subprocess.PIPE,
                                        stderr=subprocess.PIPE,
                                        shell=True
                                   )
        self.data[command] = process
        
    @property
    def threads(self):
        '''Return a list of all running threads'''
        return self.data.values()
        
    def run(self, command):
        '''Spawn a thread the run a process'''
        threading.Thread(target=self._run(command))