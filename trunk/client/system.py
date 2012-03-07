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

'''handles system information such as hardware and network ident'''

import socket
import psutil
import netifaces

from twisted.web import xmlrpc

import common.datatypes

class NetworkInformation(xmlrpc.XMLRPC):
    '''Provides information about the network'''
    def isLocal(self, ip):
        '''return True if the ip is considered a local address'''
        for prefix in ('127', '169', '0'):
            if ip.startswith(prefix):
                return True
        return False
    # end isLocal

    def xmlrpc_interfaces(self, local=False):
        '''Retruns all in interfaces that have an IPv4 address'''
        interfaces = []

        for name in netifaces.interfaces():
            iface = netifaces.ifaddresses(name)
            # skip any entires that does not have an IPv4
            # address
            if socket.AF_INET not in iface:
                continue

            for iface in iface[socket.AF_INET]:
                skip = False

                # if requested, check to see if this is a local
                # interface
                if not local:
                    skip = self.isLocal(iface.get('addr', ''))

                # only add the interface if it was not skipped
                if not skip:
                    interfaces.append(iface)

        return interfaces
    # end xmlrpc_interfaces

    def xmlrpc_fqdn(self):
        return socket.getfqdn()
    # end xmlrpc_fqdn

    def xmlrpc_hostname(self):
        return socket.gethostname()
    # end xmlrpc_hostname

    def xmlrpc_addresses(self):
        '''Retruns a list of ip addresses in use on the system'''
        addresses = []

        for iface in self.xmlrpc_interfaces():
            addresses.append(iface['addr'])

        return addresses
    # end xmlrpc_addresses

    def xmlrpc_broadcast(self, ip=None):
        '''returns the broadcast address for a given ip address'''
        if not ip:
            ip = self.xmlrpc_ip()

        for iface in self.xmlrpc_interfaces():
            if iface.get('addr') == ip:
                return iface.get('broadcast', '')
    # end xmlrpc_broadcast

    def xmlrpc_ip(self):
        '''
        returns the first entry in the addresses list
        or "" if it could not be found
        '''
        addresses = self.xmlrpc_addresses() or ['']
        return addresses[0]
    # end xmlrpc_ip
# end NetworkInformation


class SystemInformation(xmlrpc.XMLRPC):
    '''Provides information about the system'''
    def __toMB(self, value):
        return value / 1024 / 1024
    # end __toMB

    def xmlrpc_os(self):
        '''returns the operating system integer'''
        return common.datatypes.OS
    # end xmlrpc_os

    def xmlrpc_cpu_count(self):
        '''returns the number of processors in the system'''
        return psutil.NUM_CPUS
    # end xmlrpc_cpu_count

    def xmlrpc_load(self):
        '''returns the current cpu load as a percent'''
        return psutil.cpu_percent()
    # end xmlrpc_load

    def xmlrpc_ram_total(self):
        '''returns the total amount of ram in the system'''
        return self.__toMB(psutil.TOTAL_PHYMEM)
    # end xmlrpc_ram_total

    def xmlrpc_ram_free(self):
        '''returns the total amount of ram free'''
        return self.__toMB(psutil.TOTAL_PHYMEM-psutil.avail_phymem())
    # end xmlrpc_ram_free

    def xmlrpc_swap_total(self):
        '''returns the total amount of swap on the system'''
        return self.__toMB(psutil.total_virtmem())
    # end xmlrpc_swap_total

    def xmlrpc_swap_free(self):
        '''returns the total amount of swap free'''
        return self.__toMB(psutil.avail_virtmem())
    # end xmlrpc_swap_free
# end SystemInformation


def report(data):
    '''
    returns a dictionary of data with the values of all xmlrpc
    methods
    '''
    output = {}

    for instance_name, instance in data.items():
        output[instance_name] = {}
        for attr in dir(instance):
            # skip all non xmlrpc methods
            if not attr.startswith("xmlrpc_"):
                continue

            # only allow callable methods
            method = getattr(instance, attr)
            if not callable(method):
                continue

            value_name = attr.split("xmlrpc_")[1]
            output[instance_name][value_name] = method()

    return output
# end report
