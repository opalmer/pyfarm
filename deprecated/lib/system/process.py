# No shebang line, this module is meant to be imported
#
# INITIAL: March 29 2011
# PURPOSE: To query, modify, kill, and return general information about
#                   processes on the local system.
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

import os
import sys
import site
import subprocess

from PyQt4 import QtCore

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, "..", ".."))
site.addsitedir(root)

from lib import decorators, logger
from lib.system import hardware
from lib.utility import convert

logger = logger.Logger()

@decorators.deprecated
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

    elif hardware.osName() == "linux" or hardware.osName() == "cygwin":
        try:
            os.kill(pid, 9)

        except OSError:
            logger.error("No Such Process: %i" % pid)

    return True

def exists(pid):
    '''Return True if the requested pid exists'''
    pid = int(pid)
    logger.debug("Checking for process: %i" % pid)
    if hardware.osName() == "linux" or hardware.osName() == "cygwin":
        try:
            os.kill(pid, 0)
            logger.error("Process Exists: %i" % pid)
            return True

        except OSError:
            logger.error("No such process: %i" % pid)
            return False

    elif hardware.osName() == "windows":
        stdout, stderr = runcmd('tasklist /FI "PID eq %i"' % pid)
        stdout = ' '.join(stdout)

        if stdout.startswith("INFO: No tasks"):
            return False

    return True

def memoryUsage(pid, peak=False):
    '''
    Return the amount of memory consumed by a given process id in KB

    @param pid: Process id to search for
    @type  pid: C{int}
    @param peak: If true, show only the peak memory usage
    @type  peak: C{bool}
    '''
    pid = int(pid)

    if hardware.osName() == "linux" or hardware.osName() == "cygwin":
        path = "/proc/%i/status" % pid
        search = "VmSize"

        if peak:
            search = "VmPeak"

        if os.path.exists(path):
            for line in open(path, 'r'):
                if line.startswith(search):
                    return int(line.split()[1])

        else:
            logger.error("No such file: %s" % path)

    elif hardware.osName() == "windows":
        stdout, stderr = runcmd('tasklist /FI "PID eq %i"' % pid)
        stdout = ' '.join(stdout)

        if not stdout.startswith("INFO: No tasks"):
            return convert.kBToMB(stdout.split()[-2].replace(",",""))

    # if we can't find the memory usage, return None
    return None

def exceedsMemoryLimit(pid, limit, terminate=False):
    '''
    Do not allow the given process id to exceed a given value.  If it does we
    will either return True or kill the process.

    @param pid: The process id to check
    @type  pid: C{int}
    @param limit: The limit to impose on the given process id (in megabytes)
    @type  limit: C{int}
    @param terminate: If True, kill the proces if it exceeds the limit
    @type  terminate: C{bool}
    '''
    # ensure we are only passing integers
    pid = int(pid)
    limit = int(limit)
    usage = memoryUsage(pid)

    if usage and usage > limit:
        if terminate: kill(pid)
        return True

    return False
