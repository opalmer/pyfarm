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

'''
minor network library to return information related to
domain name services
'''

import socket

from pyfarm import datatypes

DNS_CACHE = {}

def ip(hostname=None, cache=True):
    '''
    returns the hostname of the current machine

    :param boolean cache:
        if False do not returns results from cache if the
        hostname exists in DNS_CACHE
    '''
    if hostname is None:
        hostname = socket.gethostname()

    if cache and hostname in DNS_CACHE:
        return DNS_CACHE[hostname]

    try:
        address = socket.gethostbyname(hostname)

        if not cache or hostname not in DNS_CACHE:
            DNS_CACHE[hostname] = address

        return address

    except socket.gaierror, error:
        # in some circumstances os x will not be
        # able to query it's own hostname in dns because
        # the local dns cache is expecting <hostname>.local
        # to be looked up
        if datatypes.OS == datatypes.OperatingSystem.MAC:
            hostname = "%s.local" % hostname
            address =  socket.gethostbyname(hostname)

            if not cache or hostname not in DNS_CACHE:
                DNS_CACHE[hostname] = address

            return address
        # similar situation for windows when attempting to
        # perform lookups and the local dns cache takes
        # precedence over asking the dns server
        elif datatypes.OS == datatypes.OperatingSystem.WINDOWS:
            hostname = "%s." % hostname
            address = socket.gethostbyname(hostname)

            if not cache or hostname not in DNS_CACHE:
                DNS_CACHE[hostname] = address

            return address

        raise
# end ip