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

'''
Provides common mechanisms for querying frame related information
'''

import copy

from pyfarm import datatypes
from pyfarm.db.transaction import Transaction
from pyfarm.db.tables import frames
from pyfarm.db.query import hosts, _tables, jobs

def priority(jobid, frameid=None, frame=None):
    '''
    returns the priority of the frame or frame id

    :exception ValueError:
        raised if the requed query could not be fulfilled
    '''
    return _tables.priority(frames, jobid, frameid=frameid, frame=frame)
# end priority

def priority_stats():
    '''returns the priority stats of the current frame table'''
    return _tables.priority_stats(frames)
# end priority_stats

def select(job=None, assign=True, hostname=None):
    '''
    Given no input arguments return a job id to select and process
    a frame from.

    :param boolean assign:
        if True then change the state of the first frame in the list to assign
    '''
    if job is None:
        active_jobs = jobs.active()
    else:
        active_jobs = [job]

    selected_frames = []
    jobids = [job.id for job in active_jobs]

    with Transaction(frames, system="query.frames.select") as trans:
        # construct a query to find frames that:
        # - are part of a running job
        # - are marked as waiting to run
        query = trans.query.filter(frames.c.jobid.in_(jobids))
        query = query.filter(frames.c.state == datatypes.State.QUEUED)
        query = query.order_by(frames.c.priority)
        query = query.order_by(frames.c.order)
        query = query.order_by(frames.c.frame)

        for frame in query:
            selected_frames.append(frame)

        trans.log("found %s frames matching query" % len(selected_frames))

        # if we are being told to assign the frame then take
        # the first frame, switch the state to datatypes.ASSIGN
        if assign:
            trans.log("selecting the first frame only")
            frame = selected_frames[0]
            frame.state = datatypes.State.ASSIGN

            # set the running host if a hostname was provided
            if hostname is not None:
                trans.log("setting hostname on frame to %s" % hostname)
                hostid = hosts.hostid(hostname)

                # ensure we find a host id
                if hostid is None:
                    raise TypeError("failed to find host id for %s" % hostname)

                frame.host = hostid

            # create a copy of the frame in its current state
            # so we can retrieve it later
            frame = copy.deepcopy(frame)

    # up the running count on the parent job
    if assign:
        sysname = "query.frames.select.set_running"

        with Transaction(jobs.jobs, system=sysname) as trans:
            query = trans.query.filter_by(id=frame.jobid)
            entry = query.first()
            running = entry.count_running + 1
            args = (entry.id, running)
            trans.log("setting running frame count for job %i to %i" % args)
            entry.count_running = running

            # assign the job object to the frame
            frame.job = copy.deepcopy(entry)

        return frame

    return selected_frames
# end select

if __name__ == '__main__':
    select()
