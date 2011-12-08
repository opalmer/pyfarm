# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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

'''Handles host (hardware) related information'''

import socket

from twisted.web import xmlrpc

class HostServices(xmlrpc.XMLRPC):
    '''Services specific to the host platform'''
    def xmlrpc_fqdn(self):
        return socket.getfqdn()
    # end xmlrpc_fqdn

    def xmlrpc_hostname(self):
        return socket.gethostname()
    # end xmlrpc_hostname

    def xmlrpc_ip(self):
        raise NotImplemented()
    # end xmlrpc_ip

    def xmlrpc_ram(self):
        '''returns the total amount of ram on the system'''
        raise NotImplemented()
    # end xmlrpc_ram

    def xmlrpc_free(self):
        '''returns the total amount of ram free on the system'''
        raise NotImplemented()
    # end xmlrpc_free
# end HostService
