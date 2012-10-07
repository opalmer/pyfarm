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

import threading
import datetime

from pyfarm.logger import Logger
from pyfarm.preferences import prefs
from pyfarm.utility import ScheduledRun

class Assignment(ScheduledRun, Logger):
    '''
    Assignment class which ensures that two assignment
    '''
    LOCK = threading.Lock()

    def __init__(self):
        Logger.__init__(self, self)
        ScheduledRun.__init__(self, prefs.get('master.assignment-interval'))
    # end __init__

    def run(self, force=False):
        with Assignment.LOCK: # only want one thread at a time to have access
            if not self.shouldRun(force):
                self.warning("skipping assignment, lastrun < interval")
                return

            self.lastrun = datetime.datetime.now()
            self.info("running assignment")
    # end run
# end Assignment
