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

from pyfarm.db.tables import MAX_HOSTNAME_LENGTH, MAX_IPV4_LENGTH, \
    MIN_PORT, MAX_PORT

class HostBase(object):
    '''mixin defines common attributes which any host would have'''

    hostname = Column(String(MAX_HOSTNAME_LENGTH), nullable=False, unique=True)
    ip = Column(String(MAX_IPV4_LENGTH), nullable=False, unique=True)
    subnet = Column(String(MAX_IPV4_LENGTH), nullable=False)
    port = Column(Integer, nullable=False)
    enabled = Column(Boolean, default=True)

    @validates('port')
    def validate_port(self, key, port):
        if port not in xrange(MIN_PORT, MAX_PORT+1):
            raise ValueError("port must be in range %s-%s" % (MIN_PORT, MAX_PORT))

        return True
    # end validate_port

    @validates('ip', 'subnet')
    def validate_address(self, key, ip):
        # TODO: IPv6 support
        try:
            socket.inet_aton(ip)
            return True

        except socket.error:
            raise ValueError("'%s' is not a valid %s address" % (ip, key))
    # end validate_address
# end HostBase
