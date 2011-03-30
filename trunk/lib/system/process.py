'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 29 2011
PURPOSE: To query, modify, kill, and return general information about
         processes on the local system.

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
import sys
import subprocess

from PyQt4 import QtCore

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", ".."))
if PYFARM not in sys.path: sys.path.append(PYFARM)

import lib

logger = lib.logger.Logger()

@lib.decorators.deprecated
def SimpleCommand(cmd, all=False, debug=False):
    '''
    By default this function will return the first results only
    from the request command.  Enabling all however will return
    a complete list.
    '''
    from lib import logger
    process = QtCore.QProcess()

    # start process and wait for it complete
    process.start(cmd)

    if debug:
        logger.debug("Starting process PID: %i" % process.pid())

    if not process.waitForStarted(): return False
    if not process.waitForFinished(): return False

    if debug:
        logger.debug("Process Complete")

    results = process.readAll().data()

    if all: return results
    else:   return results.split(os.linesep)[0]

def runcmd(cmd, wait=True):
    '''Run the command in a subprocess and return the results'''
    proc = subprocess.Popen(
                            cmd, shell=True, stdin=subprocess.PIPE,
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE
                            )

    if wait:
        proc.wait()
        stdout = proc.stdout.read().split(os.linesep)
        stdout = [line.strip() for line in stdout]
        stderr = proc.stderr.read().split(os.linesep)
        stderr = [line.strip() for line in stderr]
        return stdout, stderr

    return None, None

def kill(pid):
    '''Kill process by id'''
    if os.name == "nt":
        stdout, stderr = runcmd("taskkill /PID %i /F /T" % pid)

        if not stdout[0] or "ERROR" in stderr[0]:
            logger.error("No Such Process: %i" % pid)
            return False

    else:
        try:
            os.kill(pid, 9)

        except OSError:
            logger.error("No Such Process: %i" % pid)

    return True

def running(pid):
    '''Return true if the requested process is running'''
    pid = int(pid)
    if os.name == "nt":
        pass

    else:
        try:
            os.kill(pid, 0)

        except OSError:
            logger.error("No such process: %i" % pid)
            return False

        else:
            logger.debug("Process is Running: %i" % pid)
            return True

# cleanup objects
del CWD, PYFARM, lib
