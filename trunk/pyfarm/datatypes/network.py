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
import logging

import netifaces
from twisted.python import log

from pyfarm.preferences import prefs
from pyfarm import errors

__all__ = [
    'INTERFACES', 'ADDRESSES', 'IP', 'SUBNET',
    'INTERFACE', 'HOSTNAME', 'FQDN'
]

# setup network information
INTERFACES = {}
ADDRESSES = {}
IP = None
SUBNET = None
INTERFACE = None
HOSTNAME = socket.gethostname()
FQDN = socket.getfqdn(HOSTNAME)

for ifacename in netifaces.interfaces():
    interface = netifaces.ifaddresses(ifacename)

    # TODO: add support for IPv6
    for address in interface.get(socket.AF_INET, []):
        if 'addr' in address:
            INTERFACES[ifacename] = address
            addr = address['addr']
            ADDRESSES[ifacename] = addr

        elif 'addr' not in address:
            log.msg("%s does not have an address entry, skipping" % ifacename)
            continue

        elif addr.startswith("127.") or addr.startswith("0.0"):
            log.msg("%s seems to be a local adapter, skipping" % ifacename)
            continue

        # try to resolve the hostname and use it for
        # verification
        try:
            log.msg("looking up hostname for address %s" % addr)
            name, aliaslist, addresslist = socket.gethostbyaddr(addr)
            hostname = name.split(".")[0]

            if IP is None and \
               hostname == HOSTNAME or \
               name in aliaslist or \
               hostname in aliaslist:
                IP = addr
                SUBNET = address.get('netmask')
                INTERFACE = ifacename

        except socket.herror:
            log.msg(
                "failed to retrieve hostname for %s" % address,
                level=logging.WARNING
            )
            continue

# by this point in the process these values should be set to
# their expected values
if IP is None: raise errors.NetworkSetupError("failed to resolve ip address")
if SUBNET is None: raise errors.NetworkSetupError("failed to setup subnet")

# If enabled try and use a remote host to verfy the address we bind
# to when connecting to a socket.  This does not send any data to the
# remote host.
if IP is not None and prefs.get('network.remote_addr_check.enabled'):
    for address, port in prefs.get('network.remote_addr_check.addresses'):
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
            s.connect((address, port))

            if s.getsockname()[0] == IP:
                args = (IP, address, port)
                log.msg("%s matches bound ip when connecting to %s:%s" % args)
                break

        except socket.error, error:
            msg = "failed to connect to %s:%s to check "  % (address, port)
            msg += "bound address %s" % error
            log.msg(msg, level=logging.WARNING)

        finally:
            s.close()
    else:
        msg = "failed to match IP address to bound address using "
        msg += "remote addresse(s)"
        log.msg(msg, level=logging.WARNING)

        if prefs.get('network.remote_addr_check.error'):
            raise errors.NetworkSetupError(msg)
