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

import random

from twisted.python import log as _log

import _tables

import jobs
from common import logger, datatypes
from common.db.transaction import Transaction
from common.db.tables import frames

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

def select(job=None, select=True):
    '''
    Given no input arguments return a job id to select and process
    a frame from.

    :param boolean select:
        if False then return all jobs matching the query
    '''
    if job is None:
        active_jobs = jobs.active()
    else:
        active_jobs = [job]

    selected_frames = []
    jobids = [job.id for job in active_jobs]

    with Transaction(frames, system="query.jobs.running") as trans:
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

    if select:
        return selected_frames[0]
    return selected_frames
# end select

if __name__ == '__main__':
    select()
