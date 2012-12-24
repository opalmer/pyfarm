# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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
Used to verify and submit jobs
'''

import time

from pyfarm.logger import Logger
from pyfarm.db.insert.base import Insert
from pyfarm.datatypes.enums import State
from pyfarm.db import tables

__all__ = ['Job', 'Frames']

class Frames(Insert):
    '''class for adding frames to an existing job'''
    VALID_STATES = (
        State.PAUSED, State.QUEUED, State.DONE,
        State.FAILED
    )
    def __init__(self, job=None):
        Insert.__init__(self, tables.frames)
        self.job = job
    # end __init__

    def postAdd(self, entry):
        if 'status' in entry and entry['status'] not in self.VALID_STATES:
            msg = "%s is not a valid state, valid states" % entry['state']
            msg += "are %s" % self.VALID_STATES
            raise ValueError(msg)

        entry.setdefault('job', self.job)
    # end postAdd
# end Frames


class Job(Insert, Logger):
    '''class for submitting multiple jobs as once'''
    VALID_STATES = (
        State.PAUSED, State.QUEUED, State.BLOCKED
    )

    def __init__(self):
        Insert.__init__(self, tables.jobs)
        Logger.__init__(self, self)
    # end __init__

    def postAdd(self, entry):
        if 'status' in entry and entry['status'] not in self.VALID_STATES:
            msg = "%s is not a valid state, valid states" % entry['state']
            msg += "are %s" % self.VALID_STATES
            raise ValueError(msg)
    # end postAdd

    def postCommit(self):
        total_frames = 0
        for job in self.results:
            total_frames += len(range(job.start_frame, job.end_frame, job.by_frame))

        self.debug("constructing frame entries for %s frames" % total_frames)
        start = time.time()
        frames = Frames()

        framenum = 0
        percents = []
        for job in self.results:
            for frame in xrange(job.start_frame, job.end_frame+1, job.by_frame):
                frame_data = {'frame' : frame, 'job' : job.id}
                frames.add(**frame_data)
                framenum += 1
                completion = framenum / float(total_frames)

                if completion >= .25 and 25 not in percents:
                    percents.append(25)
                    self.debug("...25% complete")

                elif completion >= .5 and 50 not in percents:
                    percents.append(50)
                    self.debug("...50% complete")

                elif completion >= .75 and 75 not in percents:
                    percents.append(75)
                    self.debug("...75% complete")

        self.debug("frame construction complete %s" % (time.time()-start))
        frames.commit()
    # end postCommit
# end Job
