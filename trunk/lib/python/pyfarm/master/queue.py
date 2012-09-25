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

import logging
from twisted.web import xmlrpc, resource
from twisted.python import log

class Queue(xmlrpc.XMLRPC):
    '''
    Manages running or terminated jobs including starting, stopping,
    state queries, and log handling.
    '''
    ASSIGNMENT = True

    def __init__(self, service):
        resource.Resource.__init__(self)
        self.service = service # connection back to main host methods
    # end __init__

    def xmlrpc_assignment(self, value=None):
        '''queries or sets the assignment'''
        if value in (True, False):
            log.msg(
#                "setting assignment loop to %s" % value,
                level=logging.DEBUG
            )
            Queue.ASSIGNMENT = value
        return Queue.ASSIGNMENT
    # end xmlrpc_assignment
# end Queue
