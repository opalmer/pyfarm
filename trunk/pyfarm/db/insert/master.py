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

'''inserts master information into the database'''

import logging
from twisted.python import log

try:
    from collections import OrderedDict

except ImportError:
    from ordereddict import OrderedDict

from pyfarm import errors
from pyfarm.db import tables
from pyfarm.db.insert import base
from pyfarm.preferences import prefs

__all__ = ['master']

def master(hostname, port=None, online=True, queue=True, assignment=True,
           error=True, drop=False):
    '''
    inserts a single master into the database

    :param hostname:
        name of the master to insert

    :param integer port:
        if provided use insert this port into the database otherwise
        use the default port from the preferences

    :param boolean online:
        marks the master as online or offline

    :param boolean queue:
        sets the queue column in the database

    :param boolean assignment:
        sets the assignment column in the database

    :param boolean drop:
        if True then existing entries of the same host will be
        dropped prior to inserting a new entry.  If entries for the provided
        host already exist and this value is False and error is True
        then a NameError will be raised

    :param boolean error:
        if True raise an error if we find duplicate hosts and drop is False

    :exception pyfarm.errors.DuplicateHosts:
        raised if we the hosts already exists in the database and drop
        is not True

    :return:
        returns the inserted id
    '''
    log.msg("preparing to insert new master")

    data = OrderedDict()

    data['hostname'] = hostname
    data['online'] = online
    data['queue'] = queue
    data['assignment'] = assignment
    data['port'] = port or prefs.get('network.ports.master')

    # iterate over all values we just created and
    # log them
    for key, value in data.iteritems():
        log.msg("...%s: %s" % (key, value))

    # find existing hosts
    select = tables.masters.select(tables.masters.c.hostname == data['hostname'])
    existing_hosts = select.execute().fetchall()
    host_count = len(existing_hosts)

    if host_count:
        if not drop and error:
            raise errors.DuplicateHost(data['hostname'], tables.masters)

        elif not error:
            log.msg("found existing entries for %s, skipping" % data['hostname'])
            return

        elif drop:
            log.msg(
                "dropping entries for %s" % data['hostname'],
                level=logging.WARNING
            )
            for host in existing_hosts:
                log.msg("removing host %s" % host.id)
                delete = tables.masters.delete(tables.masters.c.id == host.id)
                delete.execute()
    else:
        resulting_ids = base.insert(tables.masters, 'hostname', data)

        if isinstance(resulting_ids, list):
            return resulting_ids[0]
# end master

