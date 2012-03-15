# No shebang line, this module is meant to be imported
#
# INITIAL: Nov 13 2011
# PURPOSE: Used to create and manage a process
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

import time
import random
import logging

from common import logger, rpc
from common.db import query

from twisted.internet import threads

class AssignWorkToClient(logger.LoggingBaseClass):
    '''
    class which retrieves a job from the database and sends
    it to the requesting client
    '''
    def __init__(self, hostname):
        self.hostname = hostname
    # end __init__

    def __call__(self):
        '''called by the server in the main thread to retrieve work'''
        self.log("retrieving work for %s" % self.hostname)
        frame = query.frames.select(hostname=self.hostname)
        args = (self.hostname, frame.job.id, frame.id)
        self.log("found work for client %s (jobid: %i frameid: %i)" % args)
        return frame
    # end __call__

    def rejectRequest(self, error):
        '''
        if the query failed then inform the client we cannot accept the request
        right now
        '''
        self.log(
            "%s, rejecting request!" % error.getErrorMessage(),
            level=logging.ERROR
        )
        # rpc.Connection(self.hostname, port=<port>)
        # rpc.rejectRequest()
    # end rejectRequest

    def sendWork(self, frame):
        '''called after __call__ has returned data'''
        self.log("sending work to %s" % self.hostname)
        print self.hostname, frame
    # end sendWork
# end AssignWorkToClient
