'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 28 2010
PURPOSE: To query and return information about the local system

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

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

# From Python
import os
from PyQt4 import QtCore

MODULE   = "lib.system.Utility"
LOGLEVEL = 4

def SimpleCommand(cmd, all=False):
    '''
    By default this function will return the first results only
    from the request command.  Enabling all however will return
    a complete list.
    '''
    # Logger must be imported here due to path issues with
    #  concurrency.
    from lib.Logger import Logger

    process = QtCore.QProcess()
    log = Logger("Utility.RunCommand", LOGLEVEL)

    # start process and wait for it complete
    process.start(cmd)
    log.debug("Starting process PID: %i" % process.pid())
    if not process.waitForStarted(): return False
    if not process.waitForFinished(): return False
    log.debug("Process Complete")
    return process
    #results = process.readAll().data()

    # return results
    #if all: return results
    #else:   return results.split(os.linesep)[0]

def backtrackDirs(path, levels=1):
    '''Given a path backtrack the number of requested levels'''
    for i in range(0, levels):
        path = os.path.dirname(os.path.abspath(path))

    return path
