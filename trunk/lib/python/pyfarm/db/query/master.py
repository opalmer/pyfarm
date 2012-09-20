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
import random

from twisted.python import log

from pyfarm.db.transaction import Transaction
from pyfarm.db.tables import masters, hosts
from pyfarm import errors

def exists(hostname):
    '''
    Returns True if the given master is preset in the table.  In
    general this function should not need to be used and is only
    provided for convenience.
    '''
    if not isinstance(hostname, (str, unicode)):
        raise TypeError("hostname must be a string")

    log.msg("checking to see if %s is in the master table" % hostname)
    select = masters.select(masters.c.hostname == hostname)
    return bool(select.execute().first())
# end exists

def port(hostname):
    '''returns the master for the provided hostname'''
    if not isinstance(hostname, (str, unicode)):
        raise TypeError("hostname must be a string")

    with Transaction(masters) as trans:
        host = trans.query.filter_by(hostname=hostname).first()
        if host is None:
            log.msg(
                "master %s is not in the database, using default port" % hostname,
                level=logging.WARNING
            )
            raise errors.HostNotFound(match_data=hostname, table=masters)

        return host.port
# end port

def online(hostname=None):
    '''
    If hostname is a string then query if the master with the provided
    hostname is onine.  If hostname is not provided however then
    return any master that is currently online

    :exception pyfarm.errors.HostNotFound:
        raised if the requested master is not in the master table

    :exception pyfarm.errors.HostsOffline:
        raised if we did not find any online hosts
    '''
    with Transaction(masters) as trans:
        if isinstance(hostname, (str, unicode)):
            host = trans.query.filter_by(hostname=hostname).first()
            if host is None:
                raise errors.HostNotFound(
                    match_data=hostname,
                    table=masters
                )
            return host.online

        elif hostname is None:
            online_hosts = trans.query.filter_by(online=True).all()
            log.msg("randomly selecting online host")
            if not online_hosts:
                raise errors.HostsOffline(table=masters)
            else:
                return random.choice([entry.hostname for entry in online_hosts])
# end online


def get(hostname):
    '''returns the master for the provided hostname'''
    if not isinstance(hostname, (str, unicode)):
        raise TypeError("hostname must be a string")

    with Transaction(hosts) as trans:
        host = trans.query.filter_by(hostname=hostname).first()
        if host is None:
            raise errors.HostNotFound(
                match_data=hostname,
                table=hosts
            )
        return host.master
# end get
