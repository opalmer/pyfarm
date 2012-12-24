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

import socket
import netifaces

from pyfarm.preferences import prefs
from pyfarm import errors
from pyfarm.logger import Logger

logger = Logger(__name__)

# setup network information
INTERFACES = {}
ADDRESSES = {}
IP = None
BOUND_IP = None
SUBNET = None
INTERFACE = None
HOSTNAME = socket.gethostname()
FQDN = socket.getfqdn(HOSTNAME)

# Connect to a remote host and bet the bound address.  This is mainly
# used as a fallback in case the code below cannot resolve the hostname
# from the ip address
for address, port in prefs.get('network.remote_addr_check.addresses'):
    logger.debug("using %s:%s to check for bound address" % (address, port))
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect((address, port))
        BOUND_IP = s.getsockname()[0]
        logger.debug("bound address is %s" % BOUND_IP)
        break

    except socket.error, error:
        msg = "failed to connect to %s:%s to check "  % (address, port)
        msg += "bound address %s" % error
        logger.warning(msg)

    finally:
        s.close()

else:
    msg = "failed to match IP address to bound address using "
    msg += "remote addresse(s)"
    logger.warning(msg)

for ifacename in netifaces.interfaces():
    interface = netifaces.ifaddresses(ifacename)

    # TODO: add support for IPv6
    for address in interface.get(socket.AF_INET, []):
        if IP is not None:
            break

        elif 'addr' in address:
            INTERFACES[ifacename] = address
            addr = address['addr']
            ADDRESSES[ifacename] = addr

        elif 'addr' not in address:
            logger.debug("%s does not have an address entry, skipping" % ifacename)
            continue

        if addr.startswith("127.") or addr.startswith("0.0.") or addr.startswith("169.254."):
            logger.debug("%s seems to be a local adapter, skipping" % ifacename)
            continue

        # try to resolve the hostname and use it for
        # verification
        try:
            logger.debug("looking up hostname for address %s" % addr)
            name, aliaslist, addresslist = socket.gethostbyaddr(addr)
            hostname = name.split(".")[0]

        except socket.herror, error:
            logger.warning("failed to retrieve hostname for %s" % address)

            if addr == BOUND_IP:
                IP = addr
                SUBNET = address.get('netmask')
                INTERFACE = ifacename
                logger.debug("matched %s to bound ip, using it instead" % addr)
                break

        else:
            if IP is None and \
               hostname == HOSTNAME or \
               name in aliaslist or \
               hostname in aliaslist:
                IP = addr
                SUBNET = address.get('netmask')
                INTERFACE = ifacename
            else:
                logger.debug("%s does not map to the local host's name" % addr)

# by this point in the process these values should be set to
# their expected values
if IP is None:
    raise errors.NetworkSetupError("failed to resolve ip address")

if SUBNET is None:
    raise errors.NetworkSetupError("failed to setup subnet")

HOSTID = None # populated by the top level process
