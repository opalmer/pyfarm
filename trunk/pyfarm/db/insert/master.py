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
from pyfarm.db import tables, session
from pyfarm.preferences import prefs

__all__ = ['master']

def master(hostname, port=None, online=True, queue=True, assignment=True,
           typecheck=True, drop=False):
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

    :exception pyfarm.errors.DuplicateHosts:
        raised if we the hosts already exists in the database and drop
        is not True

    :return:
        returns the inserted id
    '''
    log.msg("preparing to insert new master")

    data = OrderedDict()

    data['hostname'] = hostname

    if port is None:
        port = prefs.get('network.ports.master')

    # check to ensure all the values we have constructed
    # are what we are expecting
    if typecheck:
        nonetype = None.__class__
        type_check = {
            'hostname' : str, 'port' : int, 'online' : bool,
            'queue' : bool, 'assignment' : bool
        }

        for key, expected_types in type_check.iteritems():
            value = data.get(key)
            if not isinstance(value, expected_types):
                args = (key, expected_types, type(value))
                raise TypeError("unexpected type for %s, expected %s got %s" % args)

    # find existing hosts
    select = tables.masters.select(tables.masters.c.hostname == data['hostname'])
    existing_hosts = select.execute().fetchall()
    host_count = len(existing_hosts)

    if host_count:
        log.msg("found existing existing entries for %s" % data['hostname'])

        if not drop:
            raise errors.DuplicateHost(data['hostname'], tables.masters)
# end master

