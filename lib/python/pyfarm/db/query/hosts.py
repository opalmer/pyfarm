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
Provides common mechanisms for manipulating host information in the database
'''

from pyfarm.datatypes.network import FQDN
from pyfarm.db.contexts import Session
from pyfarm.db.tables import hosts
from pyfarm.logger import Logger

logger = Logger(__name__)

def exists(hostname=None):
    '''
    Returns True if the given hostname is preset in the table.  In
    general this function should not need to be used and is only
    provided for convenience.
    '''
    hostname = hostname or FQDN
    logger.debug("checking to see if %s is in the host table" % hostname)
    select = hosts.select(hosts.c.hostname == hostname)
    return bool(select.execute().first())
# end exists

def master(hostname):
    '''returns the master for the provided hostname'''
    with Session(hosts) as trans:
        host = trans.query.filter_by(hostname=hostname).first()
        return host.master
# end master

def hostlist(online=True):
    '''
    Returns a lists of hosts currently in the database.  Depending
    on the input keyword this function will either return the online hosts,
    offline hosts, or all hosts if None is provided as a value
    '''
    results = []
    with Session(hosts) as trans:
        if online is None:
            for instance in trans.query:
                results.append(instance.hostname)
        else:
            for instance in trans.query.filter_by(online=online):
                results.append(instance.hostname)

    return results
# end hostlist

def hostid(hostname):
    '''
    returns the hostid for a given hostname

    :param string hostname:
        the hostname to lookup in the database

    :rtype None or string:
    '''
    with Session(hosts) as trans:
        query = trans.query.filter(hosts.c.hostname == hostname).first()
        if query is None:
            trans.error("failed to find host %s in the database" % hostname)
            return None

        return query.id
# end hostid
