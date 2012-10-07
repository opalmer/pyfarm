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

from pyfarm.logger import Logger
from pyfarm.preferences import prefs

from twisted.internet.error import ConnectionRefusedError

class AssignWorkToClient(Logger):
    '''
    class which retrieves a job from the database and sends
    it to the requesting host
    '''
    def __init__(self, hostname):
        Logger.__init__(self, self)
        self.hostname = hostname
    # end __init__

    def __call__(self):
        '''called by the master in the main thread to retrieve work'''
        # TODO: update hosts (frames column) database with assigned frame
        self.debug("retrieving work for %s" % self.hostname)
        frame = frames.select(hostname=self.hostname)
        args = (self.hostname, frame.job.id, frame.id)
        self.debug("found work for host %s (jobid: %i frameid: %i)" % args)
        self.frame = frame
        return frame
    # end __call__

    def rejectRequest(self, error):
        '''
        if the query failed then inform the host we cannot accept the request
        right now
        '''
        self.critical("TODO: reset frame [and] job states")
        self.critical("TODO: handle multiple types of errors")

        if error.type == ConnectionRefusedError:
            self.error(
                "connection was refused to %s, resetting database!" % self.hostname
            )
        elif error.type == IndexError:
            self.error("no jobs in database!")

        self.debug("error type: %s" % error.type)
        self.error("%s, rejecting request!" % error.getErrorMessage())
    # end rejectRequest

    def sendWork(self, frame):
        '''called after __call__ has returned data'''
        def success(frame):
            self.debug("work sent to %s" % self.hostname)
        # end success

        def failure(error):
            self.error("rpc call to send work failed: %s" % error)
            self.rejectRequest(error)
        # end failure

        self.debug("attempting to send work to %s" % self.hostname)
        rpc = _rpc.Connection(
            self.hostname, prefs.get("network.ports.host"),
            success=success, failure=failure
        )
        rpc.call("job.assign", frame.job.id, frame.id)
    # end sendWork
# end AssignWorkToClient
