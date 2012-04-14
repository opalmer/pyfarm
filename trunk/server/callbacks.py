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

import logging

from common import logger
from common.db import query
from common.net import rpc as _rpc
from common.preferences import prefs

from twisted.internet.error import ConnectionRefusedError

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
        # TODO: update hosts (frames column) database with assigned frame
        self.log("retrieving work for %s" % self.hostname)
        frame = query.frames.select(hostname=self.hostname)
        args = (self.hostname, frame.job.id, frame.id)
        self.log("found work for client %s (jobid: %i frameid: %i)" % args)
        self.frame = frame
        return frame
    # end __call__

    def rejectRequest(self, error):
        '''
        if the query failed then inform the client we cannot accept the request
        right now
        '''
        self.log(
            "TODO: reset frame [and] job states",
            level=logging.CRITICAL
        )
        self.log(
            "TODO: handle multiple types of errors",
            level=logging.CRITICAL
        )

        if error.type == ConnectionRefusedError:
            self.log(
                "connection was refused to %s, resetting database!" % self.hostname,
                level=logging.ERROR
            )
        elif error.type == IndexError:
            self.log(
                "no jobs in database!",
                level=logging.ERROR
            )

        self.log(
            "error type: %s" % error.type,
            level=logging.DEBUG
        )
        self.log(
            "%s, rejecting request!" % error.getErrorMessage(),
            level=logging.ERROR
        )

    # end rejectRequest

    def sendWork(self, frame):
        '''called after __call__ has returned data'''
        def success(frame):
            self.log("work sent to %s" % self.hostname)
        # end success

        def failure(error):
            self.log(
                "rpc call to send work failed: %s" % error,
                level=logging.ERROR
            )
            self.rejectRequest(error)
        # end failure

        self.log("attempting to send work to %s" % self.hostname)
        rpc = _rpc.Connection(
            self.hostname, prefs.get("network.ports.client"),
            success=success, failure=failure
        )
        rpc.call("job.assign", frame.job.id, frame.id)
    # end sendWork
# end AssignWorkToClient
