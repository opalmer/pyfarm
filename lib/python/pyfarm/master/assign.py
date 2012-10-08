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

import datetime
import threading
from sqlalchemy import orm

from pyfarm.logger import Logger
from pyfarm.preferences import prefs
from pyfarm.utility import ScheduledRun
from pyfarm.db import session, tables, contexts

class Assignment(ScheduledRun, Logger):
    '''
    Assignment class which ensures that two assignment
    '''
    LOCK = threading.Lock()

    def __init__(self):
        Logger.__init__(self, self)
        ScheduledRun.__init__(self, prefs.get('master.assignment-interval'))
    # end __init__

    def getWork(self):
        '''
        return a dictionary of hosts and frame ids to assign to assign to
        each host
        '''
        assignments = {}
        connection = session.ENGINE.connect()

        with contexts.Connection(connection):
            scoped_session = orm.scoped_session(session.Session)
            query = scoped_session.query(tables.hosts)
            online_hosts = query.filter(tables.hosts.c.online == True)

            # nothing we can do of there are not any hosts
            # online
            if not online_hosts.count():
                self.warning("no hosts online to assign to")
                return

            for host in online_hosts:
                print host
    # end getWork

    def run(self, force=False):
        with Assignment.LOCK: # only want one thread at a time to have access
            # check one last time before we attempt to run
            # the assignment
            if not self.shouldRun(force):
                self.warning("skipping assignment, lastrun < interval")
                return

            self.info("running assignment")

            work = self.getWork()

            self.debug("finished assignment")
            self.lastrun = datetime.datetime.now()
    # end run
# end Assignment
