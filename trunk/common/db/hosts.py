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

from __future__ import with_statement

'''
Provides common mechanisms for manipulating host information in the database
'''

import types

from twisted.python import log

from transaction import Transaction
from tables import hosts

def __convert_resources(data):
    '''converts raw resource data into valid data for a table'''
    # populate the initial results
    network = data['network']
    system = data['system']
    results = {
        "hostname" : network['hostname'],
        "ip" : network['ip'],
        "os" : system['os'],
        "ram_total" : system['ram_total'],
        "ram_usage" : system['ram_total']-system['ram_free'],
        "swap_total" : system['swap_total'],
        "swap_usage" : system['swap_total']-system['swap_free'],
        "cpu_count" : system['cpu_count'],
        "online" : system['online']
    }

    # add the netmask for the interface with the matching ip address
    for interface in network['interfaces']:
        if interface['addr'] == network['ip']:
            results['subnet'] = interface['netmask']

    return results
# end __convert_resources

def exists(hostname):
    '''
    Returns True if the given hostname is preset in the table.  In
    general this function should not need to be used and is only
    provided for convenience.
    '''
    with Transaction(hosts) as trans:
        host = trans.query.filter_by(hostname=hostname).first()
        if isinstance(host, types.NoneType):
            return False

        return False
# end exists

def update_resources(hostname, data):
    '''
    Inserts the hostname into the database if it does not exist, updates
    it if it does

    :rtype list:
        returns the fields that were updated for the given host
    '''
    resources = __convert_resources(data)

    log.msg("attempting to update host information for %s" % hostname)
    with Transaction(hosts) as trans:
        host = trans.query.filter_by(hostname=hostname).first()

        if isinstance(host, types.NoneType):
            insert = trans.table.insert()
            insert.execute(resources)
            log.msg("inserted %s into %s" % (hostname, trans.tablename))
            return resources.keys()

        else:
            # iterate over all keys in the resources
            # dictionary and update the table with
            # new values
            fields = [] # store a list of updated fields
            for key, value in resources.items():
                if getattr(host, key) != value:
                    setattr(host, key, value)
                    fields.append(key)

            if fields:
                log.msg("updated fields for %s: %s" % (hostname, fields))

            return fields
# end update_resources
