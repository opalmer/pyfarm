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
This is just a very simple module to handle retrieval and initial process
of a new job from the database.  This module may later be expanded on or
replaced.
'''

import psutil
import socket
import logging

from pyfarm.datatypes.network import HOSTNAME

from twisted.python import log as _log


def allocateFrame(hostname=None, cpus=None, ram=None, update=False):
    '''
    Retrieves a frame from the database according to the current
    hardware or provided arguments.  After retrieving the frame
    we then "allocate" the frame to this host and mark it as running.

    All arguments all refer to the requesting system (where we
    plan to run the job).

    :param boolean update:
        if True update the given host in the database

    :rtype tuple:
        returns the jobid and frame id allocated
    '''
    def log(msg, level=logging.DEBUG):
        _log.msg(msg, level=level, system="queue.allocateFrame")
    # end log

    hostname = hostname or HOSTNAME

    if cpus is None:
        cpus = psutil.NUM_CPUS

    if ram is None:
        ram = (psutil.TOTAL_PHYMEM-psutil.avail_phymem()) / 1024 / 1024

    args = (hostname, ram, cpus)
    log("preparing to allocate frame for %s (ram: %i, cpus: %i)" % args)

    # retrieve the highest priority job
    job = jobs.select()
    frame = frames.select()
# end allocateFrame

if __name__ == '__main__':
    allocateFrame()
