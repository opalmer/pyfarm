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

from __future__ import with_statement

import socket
import psutil
import logging

from twisted.python import log

from common import logger
from common.db.transaction import Transaction
from common.db.tables import hosts

def __convert_resources(data):
    '''converts raw resource data into valid data for a table'''
    # populate the initial results
    network = data['network']
    system = data['system']

    # builds the results row from the incoming host data
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
        if host is None:
            return False

        return False
# end exists

def update_resources(hostname=None, data=None):
    '''
    Inserts the hostname into the database if it does not exist, updates
    it if it does

    :rtype list:
        returns the fields that were updated for the given host
    '''
    if hostname is None:
        hostname = socket.getfqdn()

    log.msg("attempting to update host information for %s" % hostname)

    with Transaction(hosts, system="query.hosts.update_resources") as trans:
        host = trans.query.filter(hosts.c.hostname == hostname).first()

        if data is not None:
            resources = __convert_resources(data)
            if host is None:
                insert = trans.table.insert()
                insert.execute(resources)
                trans.log("inserted %s into %s" % (hostname, trans.tablename))
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
                    trans.log("updated fields for %s: %s" % (hostname, fields))

            return fields

        # if we are not provided any data then update the ram
        # and swap usage fields
        else:
            if host is None:
                trans.log(
                    "%s does not exist in the database" % hostname,
                    level=logging.WARNING
                )
                return

            trans.log("updating resource usage")
            host.ram_usage = psutil.used_phymem() / 1024 / 1024
            host.swap_usage = psutil.used_virtmem() / 1024 / 1024
# end update_resources

def hostlist(online=True):
    '''
    Returns a lists of hosts currently in the database.  Depending
    on the input keyword this function will either return the online hosts,
    offline hosts, or all hosts if None is provided as a value
    '''
    results = []
    with Transaction(hosts) as trans:
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
    with Transaction(hosts, system="common.db.query.hosts.hostid") as trans:
        query = trans.query.filter(hosts.c.hostname == hostname).first()
        if query is None:
            trans.log("failed to find host %s in the database" % hostname)
            return None

        return query.id
# end hostid
