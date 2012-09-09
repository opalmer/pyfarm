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

import socket
import psutil
import netifaces

from pyfarm.datatypes.functions import notimplemented

class network:
    if  hasattr(psutil, 'network_io_counters'):
        SENT = property(lambda self: psutil.network_io_counters().bytes_sent / 1024 / 1024)
        RECV = property(lambda self: psutil.network_io_counters().bytes_recv / 1024 / 1024)
    else:
        SENT = property(lambda self: notimplemented('network_io_counters'))
        RECV = property(lambda self: notimplemented('network_io_counters'))

    HOSTNAME = socket.gethostname()
    FQDN = socket.getfqdn(HOSTNAME)

    # setup network information
    INTERFACES = {}
    ADDRESSES = {}
    IP = None
    SUBNET = None
    INTERFACE = None

    for ifacename in netifaces.interfaces():
        interface = netifaces.ifaddresses(ifacename)

        # TODO: add support for IPv6
        for address in interface.get(socket.AF_INET, []):
            # skip addresses that do not contain an address entry
            if 'addr' in address:
                INTERFACES[ifacename] = address

            addr = address['addr']
            ADDRESSES[ifacename] = addr

            # skip local/private entries
            try:
                if addr.startswith("127.") or addr.startswith("0."):
                    continue

                try:
                    name, aliaslist, addresslist = socket.gethostbyaddr(addr)
                    hostname = name.split(".")[0]

                    if IP is None and\
                       hostname == HOSTNAME or\
                       name in aliaslist or\
                       hostname in aliaslist:
                        IP = addr
                        SUBNET = address.get('netmask')
                        INTERFACE = ifacename

                    del hostname, name, aliaslist, addresslist

                except socket.herror:
                    continue

            finally:
                del address, addr

        # One last attempt to retrieve the correct ip using strictly
        # our hostname and dns.  The above will take care of the majority of
        # cases however sometimes we have to fall back on DNS alone
        if IP is None:
            try:
                addr = socket.gethostbyname(HOSTNAME)
                IP = addr
                del addr

            except socket.herror:
                try:
                    addr = socket.gethostbyname(FQDN)
                    IP = addr
                    del addr

                except socket.herror:
                    pass

        if IP is None:
            raise ValueError("failed to retrieve ip address for %s" % HOSTNAME)

        if SUBNET is None:
            for ifacename, interface in INTERFACES.iteritems():
                if interface.get('addr') == IP:
                    SUBNET = interface.get('netmask')
                    break

        # if the interface has not been setup yet then
        #
        if INTERFACE is None:
            for ifacename, interface in INTERFACES.iteritems():
                if interface.get('addr') == IP:
                    INTERFACE = ifacename
                    break

        # if the preferences call for it perform a check to
        # determine if the 'bound' ip is the same one
        # we just resolved

        del ifacename
        del interface
# end network
