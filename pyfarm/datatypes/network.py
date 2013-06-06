# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

import socket
from warnings import warn
import netifaces

from pyfarm.ext.config.core.loader import Loader
from pyfarm import errors

prefs = Loader("network.yml")

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
for address, port in prefs.get('remote_addr_check.addresses'):
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect((address, port))
        BOUND_IP = s.getsockname()[0]
        break

    except socket.error, error:
        msg = "failed to connect to %s:%s to check " % (address, port)
        msg += "bound address %s" % error
        warn(msg, RuntimeWarning)

    finally:
        s.close()

else:
    msg = "failed to match IP address to bound address using "
    msg += "remote addresse(s)"
    warn(msg, RuntimeWarning)

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
            continue

        if addr.startswith("127.") or addr.startswith("0.0.") or addr.startswith("169.254."):
            continue

        # try to resolve the hostname and use it for
        # verification
        try:
            name, aliaslist, addresslist = socket.gethostbyaddr(addr)
            hostname = name.split(".")[0]

        except socket.herror, error:
            warn("failed to retrieve hostname for %s" % address, RuntimeWarning)

            if addr == BOUND_IP:
                IP = addr
                SUBNET = address.get('netmask')
                INTERFACE = ifacename
                break

        else:
            if IP is None and \
               hostname == HOSTNAME or \
               name in aliaslist or \
               hostname in aliaslist:
                IP = addr
                SUBNET = address.get('netmask')
                INTERFACE = ifacename

# by this point in the process these values should be set to
# their expected values
if IP is None:
    raise errors.NetworkSetupError("failed to resolve ip address")

if SUBNET is None:
    raise errors.NetworkSetupError("failed to setup subnet")

HOSTID = None  # populated by the top level process
