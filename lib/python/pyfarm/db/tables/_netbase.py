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
from sqlalchemy import Column
from sqlalchemy.orm import validates
from sqlalchemy.types import String, Boolean, Integer

from pyfarm.net import openport
from pyfarm.logger import Logger
from pyfarm.db.tables import MAX_HOSTNAME_LENGTH, MAX_IPV4_LENGTH, \
    MIN_PORT, MAX_PORT

logger = Logger(__name__)

class NetworkHost(object):
    '''mixin which defines common attributes that all network nodes have'''
    hostname = Column(String(MAX_HOSTNAME_LENGTH), nullable=False, unique=True)
    ip = Column(String(MAX_IPV4_LENGTH), nullable=False, unique=True)
    subnet = Column(String(MAX_IPV4_LENGTH), nullable=False)
    port = Column(Integer, nullable=False)
    enabled = Column(Boolean, default=True)

    def __init__(self, hostname, ip, subnet, port, enabled=False):
        self.hostname = hostname
        self.ip = ip
        self.subnet = subnet

        # autoselect if an integer was not provided for the port
        if not isinstance(port, int):
            port = openport()
            logger.debug("port not provided, using %s" % port)

        self.port = port

        if enabled is not None:
            self.enabled = enabled
    # end __init__

    @validates('port')
    def validate_port(self, key, port):
        if port not in xrange(MIN_PORT, MAX_PORT+1):
            raise ValueError("port must be in range %s-%s" % (MIN_PORT, MAX_PORT))

        return port
    # end validate_port

    @validates('ip', 'subnet')
    def validate_address(self, key, ip):
        # TODO: IPv6 support
        try:
            socket.inet_aton(ip)

        except socket.error:
            raise ValueError("'%s' is not a valid %s address" % (ip, key))

        # inet_aton does not catch problems with addresses that have invalid
        # length to begin with
        length = len([ i for i in ip.split(".") if i.strip() ])
        if length != 4:
            msg = "invalid length for IPv4 address, "
            msg += "expected 4 groups but found %s" % length
            raise ValueError(msg)

        return ip
    # end validate_address
# end HostBase

