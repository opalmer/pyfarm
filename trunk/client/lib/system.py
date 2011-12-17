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

'''handles system information such as hardware and network ident'''

import socket
import psutil

from twisted.web import xmlrpc

class NetworkInformation(xmlrpc.XMLRPC):
    '''Provides information about the network'''
    def xmlrpc_fqdn(self):
        return socket.getfqdn()
    # end xmlrpc_fqdn

    def xmlrpc_hostname(self):
        return socket.gethostname()
    # end xmlrpc_hostname

    def xmlrpc_ip(self):
        raise NotImplemented()
    # end xmlrpc_ip
# end NetworkInformation


class SystemInformation(xmlrpc.XMLRPC):
    '''Provides information about the system'''
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
        return psutil.TOTAL_PHYMEM / 1024 / 1024
    # end xmlrpc_ram_total

    def xmlrpc_ram_free(self):
        '''returns the total amount of ram free'''
        return psutil.avail_phymem() / 1024 / 1024
    # end xmlrpc_ram_free
# end SystemInformation
