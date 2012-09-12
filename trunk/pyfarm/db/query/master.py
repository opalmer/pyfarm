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
functions for querying information related to one or more
of the master hosts
'''

from __future__ import with_statement

import logging

from twisted.python import log

from pyfarm.preferences import prefs
from pyfarm.db.transaction import Transaction
from pyfarm.db.tables import masters
from pyfarm import errors

def exists(hostname):
    '''
    Returns True if the given master is preset in the table.  In
    general this function should not need to be used and is only
    provided for convenience.
    '''
    log.msg("checking to see if %s is in the master table" % hostname)
    select = masters.select(masters.c.hostname == hostname)
    return bool(select.execute().first())
# end exists

def port(hostname):
    '''returns the master for the provided hostname'''
    with Transaction(masters) as trans:
        host = trans.query.filter_by(hostname=hostname).first()
        if host is None:
            log.msg(
                "master %s is not in the database, using default port" % hostname,
                level=logging.WARNING
            )
            return prefs.get('network.ports.master')

        return host.port
# end port

def online(hostname):
    '''
    Returns a lists of hosts currently in the database.  Depending
    on the input keyword this function will either return the online hosts,
    offline hosts, or all hosts if None is provided as a value
    '''
    with Transaction(masters) as trans:
        host = trans.query.filter_by(hostname=hostname).first()
        if host is None:
            raise errors.HostNotFound(hostname, masters)
# end hostlist
