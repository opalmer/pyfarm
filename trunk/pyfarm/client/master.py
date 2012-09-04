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

'''module for dealing with storage and retrieval of the master'''

from twisted.python import log

import socket
import logging

from pyfarm.db import query
from pyfarm import datatypes

from twisted.python import log

HOSTNAME = datatypes.Localhost.net.HOSTNAME

def get(master=None):
    '''
    :param string master:
        if provided then use this value as the master regardless of what
        the master is in the database or what the hostname is
    '''
    if master is None:
        master = query.hosts.master(HOSTNAME)

    if master is None:
        raise ValueError(
            "expected master to be a non-null value please check your input arguments to pyfarm_client"
        )

    # if we cannot resolve the provided master then we will probably
    # have trouble connecting so just fail here
    try:
        log.msg("ensuring we can resolve %s's address" % master)
        address = socket.gethostbyname(master)

    except socket.gaierror:
        log.msg(
            "master '%s' failed to resolve to a valid address" % master,
            level=logging.ERROR
        )
        raise

    return master
# end get
