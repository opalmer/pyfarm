# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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

'''inserts network information into the database'''

try:
    from collections import OrderedDict

except ImportError:
    from ordereddict import OrderedDict

from pyfarm import errors
from pyfarm.logger import Logger
from pyfarm.db import tables
from pyfarm.db.insert import base
from pyfarm.preferences import prefs
from pyfarm.datatypes import system
from pyfarm.datatypes.network import HOSTNAME, FQDN, IP, SUBNET
from pyfarm.datatypes.enums import DEFAULT_GROUPS, DEFAULT_SOFTWARE, DEFAULT_JOBTYPES, OperatingSystem

__all__ = ['host']

logger = Logger(__name__)

def host(
        hostname=None, port=None, master=None, ip=None, subnet=None, os=None,
        ram_total=None, ram_usage=None, swap_total=None, swap_usage=None,
        cpus=None, online=None, groups=None, software=None, jobtypes=None,
        error=True, drop=False
    ):
    '''
    inserts a single host into the database

    :param string hostname: local hostname if not provided
    :param integer port: the port the host is operating on
    :param string master: the master the provided host should be communicating with
    :param string ip: local address if not provided
    :param string subnet: local subnet if not provided
    :param integer os: local os if not provided
    :param integer ram_total: local ram total if not provided
    :param integer ram_usage: local ram usage if not provided
    :param integer swap_total: local swap total if not provided
    :param integer swap_usage: local swap usage if not provided

    :param boolean online:
        if True then the host should be accepting jobs (though the local host
        may override this)

    :param list groups:
        groups this machine belongs to

    :param list software:
        software which this host supports

    :param list jobtypes:
        jobtypes which this host will accept

    :param boolean drop:
        if True then existing entries of the same host will be
        dropped prior to inserting a new entry.  If entries for the provided
        host already exist and this value is False and error is True
        then a NameError will be raised

    :param boolean error:
        if True raise an error if we find duplicate hosts and drop is False

    :exception ValueError:
        raised if we are attempting to autogenerate certain values
        when the hostname provided is not considered local

    :exception KeyError:
        raised if there were problems with a value provided not
        being a part of a set or 'known values'.

    :exception pyfarm.errors.DuplicateHosts:
        raised if we the hosts already exists in the database and drop
        is not True

    :return:
        returns the inserted id
    '''
    logger.debug("preparing to insert new host")
    local = False
    data = OrderedDict()

    # setup hostname
    if hostname is None:
        hostname = HOSTNAME
        local = True

    elif hostname is not None and hostname in ('localhost', HOSTNAME, FQDN):
        local = True

    data['hostname'] = hostname

    if master is not None:
        data['master'] = master

    # setup ip address
    # hostname must be provided in order to discover
    # the ip address
    if hostname is None and ip is None:
        raise ValueError("hostname must be provided if ip is None")

    elif ip is None:
        ip = IP

    data['ip'] = ip

    # subnet setup
    if not local and subnet is None:
        raise ValueError("subnet must be provided if hostname is not local")

    elif local and subnet is None:
        subnet = SUBNET

    data['subnet'] = subnet

    # operating system setup
    if not local and os is None:
        raise ValueError("os must be provided if hostname is not local")

    elif local and os is None:
        os = system.OS

    elif os not in OperatingSystem.MAPPINGS:
        raise KeyError("no such operation system '%s'" % os)

    data['os'] = os

    # ram setup - total
    if not local and ram_total is None:
        raise ValueError("ram_total must be provided if hostname is not local")

    elif local and ram_total is None:
        ram_total = system.TOTAL_RAM

    data['ram_total'] = ram_total

    # ram setup - usage
    if local and ram_usage is None:
        ram_usage = system.TOTAL_RAM - system.ram()

    elif not local and ram_usage is None:
        raise ValueError("ram_usage must be provided when hostname is not local")

    data['ram_usage'] = ram_usage

    # swap setup - total
    if local and swap_total is None:
        swap_total = system.TOTAL_SWAP

    elif not local and swap_usage is None:
        raise ValueError("swap_total must be provided when hostname is not local")

    data['swap_total'] = swap_total

    # swap setup - usage
    if local and swap_usage is None:
        swap_usage = system.TOTAL_SWAP - system.swap()

    elif not local and swap_usage is None:
        raise ValueError("swap_usage must be provided when hostname is not local")

    data['swap_usage'] = swap_usage

    # setup cpu count
    if local and cpus is None:
        cpus = system.CPU_COUNT

    elif not local and cpus is None:
        raise ValueError("cpus must be provided when hostname is not local")

    data['cpus'] = cpus

    # online setup
    if online is None:
        online = True

    data['online'] = online

    # groups setup
    if groups is None:
        groups = DEFAULT_GROUPS

    data['groups'] = groups

    # software setup
    if software is None:
        software = DEFAULT_SOFTWARE

    data['software'] = software

    # jobtypes setup
    if jobtypes is None:
        jobtypes = DEFAULT_JOBTYPES

    data['jobtypes'] = jobtypes

    # port setup
    if port is None:
        port = prefs.get('network.ports.host')

    data['port'] = port

    # iterate over all values we just created and
    # log them
    for key, value in data.iteritems():
        # remap the os key to its 'pretty' name
        if key == "os":
            value = OperatingSystem.get(value)
        logger.debug("...%s: %s" % (key, value))

    # find existing hosts
    select = tables.hosts.select(tables.hosts.c.hostname == data['hostname'])
    existing_hosts = select.execute().fetchall()
    host_count = len(existing_hosts)

    if host_count:
        if not drop and error:
            raise errors.DuplicateHost(data['hostname'], tables.hosts)

        elif not error:
            logger.debug(
                "found existing entries for %s, skipping" % data['hostname']
            )
            return

        elif drop:
            logger.warning("dropping entries for %s" % data['hostname'])

            for host in existing_hosts:
                logger.debug("removing host %s" % host.id)
                delete = tables.hosts.delete(tables.hosts.c.id == host.id)
                delete.execute()
    else:
        resulting_ids = base.insert(tables.hosts, 'hostname', data)

        if isinstance(resulting_ids, list):
            return resulting_ids[0]
# end host
