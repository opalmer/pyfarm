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
import os
import sys

from PyQt4 import QtCore

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

LOGLEVEL = 4

def SimpleCommand(cmd, all=False, debug=False):
    '''
    By default this function will return the first results only
    from the request command.  Enabling all however will return
    a complete list.
    '''
    from lib import Logger
    process = QtCore.QProcess()

    if debug:
        log = Logger.Logger("Utility.RunCommand", LOGLEVEL)

    # start process and wait for it complete
    process.start(cmd)

    if debug:
        log.debug("Starting process PID: %i" % process.pid())

    if not process.waitForStarted(): return False
    if not process.waitForFinished(): return False

    if debug:
        log.debug("Process Complete")

    results = process.readAll().data()

    if all: return results
    else:   return results.split(os.linesep)[0]
