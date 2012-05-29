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

'''module for dealing with storage and retrieval of the master'''

from pyfarm import prefs

PORT = prefs.get('network.ports.client')

def getmaster(options):
    '''primary function for retrieving the master'''
    # ensure both --master and --set-master are not being provided
    if options.master is not None and options.set_master is not None:
        raise ValueError("--set-master and --master cannot both be defined")
# end getmaster


#
## if either master or set_master are set then we should
## setup the local MASTER variable before we continue
#if options.master or options.set_master:
#    port = prefs.get('network.ports.server')
#    master = options.master or options.set_master
#    MASTER = (master, port)
#
#if options.set_master:
#    log.msg(
#        "--set-master database calls not implemented",
#        level="NOT_IMPLEMENTED",
#        system="Client"
#    )
#
#elif options.set_master is None and options.master is None:
#    log.msg(
#        "master from database not implemented",
#        level="NOT_IMPLEMENTED",
#        system="Client"
#    )