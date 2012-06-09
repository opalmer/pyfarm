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

'''inserts network information into the database'''

from twisted.python import log

from pyfarm.datatypes import Localhost, OperatingSystem

HOSTNAME = Localhost.net.HOSTNAME
FQDN = Localhost.net.FQDN

def host(
        hostname=None, master=None, ip=None, subnet=None, os=None,
        ram_total=None, ram_usage=None, swap_total=None, swap_usage=None,
        cpu_count=None,
        typecheck=True
    ):
    '''
    inserts a single host into the database

    :param string hostname: local hostname if not provided
    :param string master:
    :param string ip: local address if not provided
    :param string subnet: local subnet if not provided
    :param integer os: local os if not provided
    :param integer ram_total: local ram total if not provided
    :param integer ram_usage: local ram usage if not provided
    :param integer swap_total: local swap total if not provided
    :param integer swap_usage: local swap usage if not provided

    :param boolean typecheck:
        if True then type check each input for errors prior to making the update

    :exception ValueError:
        raised if we are attempting to autogenerate certain values
        when the hostname provided is not considered local

    :exception KeyError:
        raised if there were problems with a value provided not
        being a part of a set or 'known values'.

hosts = sql.Table('pyfarm_hosts', metadata,
    sql.Column('id', sql.Integer, autoincrement=True, primary_key=True),
#    sql.Column('hostname', sql.String(255)),
#    sql.Column('master', sql.String(255)),
#    sql.Column('ip', sql.String(16)),
#    sql.Column('subnet', sql.String(16)),
#    sql.Column('os', sql.Integer),
#    sql.Column('ram_total', sql.Integer),
#    sql.Column('ram_usage', sql.Integer),
#    sql.Column('swap_total', sql.Integer),
#    sql.Column('swap_usage', sql.Integer),
    sql.Column('cpu_count', sql.Integer),
    sql.Column('online', sql.Boolean, nullable=False, default=True),
    sql.Column('groups', sql.String(128), default='*'),
    sql.Column('software', sql.String(256), default="*"),
    sql.Column('hold', sql.Boolean, nullable=False, default=False),
    sql.Column('dependencies', sql.PickleType, default=[])
)
    '''
    log.msg("preparing to insert new host")
    local = False
    data = {}

    if hostname is None:
        hostname = HOSTNAME
        local = True

    elif hostname is not None and hostname in ('localhost', HOSTNAME, FQDN):
        local = True

    log.msg("....hostname: %s" % hostname)
    data['hostname'] = hostname

    # hostname must be provided in order to discover
    # the ip address
    if hostname is None and ip is None:
        raise ValueError("hostname must be provided if ip is None")

    elif ip is None:
        ip = Localhost.net.IP

    log.msg(".....address: %s" % ip)
    data['ip'] = ip

    if not local and subnet is None:
        raise ValueError("subnet must be provided if hostname is not local")

    elif local and subnet is None:
        subnet = Localhost.net.SUBNET

    log.msg("......subnet: %s" % subnet)
    data['subnet'] = subnet

    if not local and os is None:
        raise ValueError("os must be provided if hostname is not local")

    elif local and os is None:
        os = Localhost.OS

    elif os not in OperatingSystem.MAPPINGS:
        raise KeyError("no such operation system '%s'" % os)

    log.msg("..........os: %s" % OperatingSystem.get(os))
    data['os'] = os

    if not local and ram_total is None:
        raise ValueError("ram_total must be provided if hostname is not local")

    elif local and ram_total is None:
        ram_total = Localhost.TOTAL_RAM

    log.msg("...ram total: %s" % ram_total)
    data['ram_total'] = ram_total

    if local and ram_usage is None:
        ram_usage = Localhost.RAM

    elif not local and ram_usage is None:
        raise ValueError("ram_usage must be provided when hostname is not local")

    log.msg("...ram usage: %s" % ram_usage)
    data['ram_usage'] = ram_usage

    if local and swap_total is None:
        swap_total = Localhost.TOTAL_SWAP

    elif not local and swap_usage is None:
        raise ValueError("swap_total must be provided when hostname is not local")

    log.msg("..swap total: %s" % swap_total)
    data['swap_total'] = swap_total

    if local and swap_usage is None:
        swap_usage = Localhost.SWAP

    elif not local and swap_usage is None:
        raise ValueError("swap_usage must be provided when hostname is not local")

    log.msg("..swap usage: %s" % swap_usage)
    data['swap_usage'] = swap_usage

    if local and cpu_count is None:
        cpu_count = Localhost.CPU_COUNT

    elif not local and cpu_count is None:
        raise ValueError("cpu_count must be provided when hostname is not local")

    log.msg("...cpus: %s" % cpu_count)
    data['cpu_count'] = cpu_count

    # check to ensure all the values we have constructed
    # are what we are expecting
    if typecheck:
        nonetype = None.__class__
        type_check = {
            'hostname' : str, 'master' : (str, nonetype), 'ip' : str,
            'subnet' : str, 'os' : int, 'ram_total' : int, 'ram_usage' : int,
            'swap_usage' : int, 'swap_total' : int, 'cpu_count' : intg
        }

        for key, expected_types in type_check.iteritems():
            value = data.get(key)
            if not isinstance(value, expected_types):
                args = (key, expected_types, type(value))
                raise TypeError("unexpected type for %s, expected %s got %s" % args)
# end host

if __name__ == "__main__":
    from pyfarm import logger
    host()
