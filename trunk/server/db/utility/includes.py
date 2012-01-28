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

def hostToTableData(data):
    '''
    Converts incoming data from host a host to a valid
    table mapping.  The incoming data is expected to come into
    server.Server.addHost from client.setMaster
    '''
    # retrieve the address, broadcast, and subnet
    address = None
    broadcast = None
    netmask = None

    # find interface data for our current ip address
    for interface in data['network']['interfaces']:
        if interface['addr'] == data['network']['ip']:
            address = interface['addr']
            broadcast = interface['broadcast']
            netmask = interface['netmask']

    # convert the incoming data
    return {
        'hostname' : data['network']['fqdn'],
        'ip' : data['network']['ip'],
        'subnet' : netmask,
        'ram_total' : data['system']['ram_total'],
        'swap_total' : data['system']['swap_total'],
        'cpu_count' : data['system']['cpu_count'],
        'online' : True,
        'software' : "NOT_IMPLEMENTED"
    }
# end hostToTableData

