'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 28 2010
PURPOSE: To query and return information about the local system

This file is part of PyFarm.
Copyright (C) 2008-2010 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

# From Python
import os.path
import subprocess

# From PyFarm
#from lib.Logger import Logger

__MODULE__ = "lib.system.Utility"
__LOGLEVEL__ = 4

def SimpleCommand(cmd):
    '''
    Run the given command and return the resulting string. Please
     note this function is for SINGLE COMMANDS only.
    '''
    #log = Logger("Utility.RunCommand")
    #log.debug("Starting process")

    proc = subprocess.Popen(cmd, shell=True,
        stdin=subprocess.PIPE, stdout=subprocess.PIPE  )

    #log.debug("Running command: %s" % cmd)
    results = proc.stdout.readline().split()[0]
    #log.debug("Process complete")

    return results

def backtrackDirs(path, levels=1):
    '''Given a path backtrack the number of requested levels'''
    for i in range(0, levels):
        path = os.path.dirname(path)

    return path
