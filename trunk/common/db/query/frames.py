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
from sqlalchemy import func

from twisted.python import log as _log

import _tables

import jobs
from common import logger
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

def select(jobid=None, min_priority=None, max_priority=None, select=True):
    '''
    Given no input arguments return a job id to select and process
    a frame from.

    :param integer min_priority:
        jobs lower than this priority will not be returned

    :param integer max_priority:
        if not None jobs higher than this priority will not be displayed

    :param boolean select:
        if False then return all jobs matching the query
    '''
    def log(msg, **kwargs):
        kwargs.update(system="query.frames.select")
        _log.msg(msg, **kwargs)
    # end log

    # select a o
    if jobid is None:
        log("auto selecting job id")
        jobid = jobs.select()

    jobids = []
    if min_priority is None and max_priority is None:
        with Transaction(jobs, system="query.jobs.select") as trans:
            p_min, p_max, p_avg = priority_stats()

            # find all jobs matching this priority (unless we did not find
            # any jobs)
            if p_max:
                for job in trans.query.filter_by(priority=p_max):
                    jobids.append(job.id)

                args = (len(jobids), p_max)
                log("found %i job(s) with priority %i" % args)

            else:
                log("no jobs found")

    elif min_priority is not None:
        with Transaction(jobs, system="query.jobs.select") as trans:
            query = trans.query.filter(jobs.c.priority > min_priority)

            # add the max priority if one was supplied
            if max_priority is not None:
                query = query.filter(jobs.c.priority < max_priority)
                args = (min_priority, max_priority)
                msg = "searching for jobs with a range of %i -> %i" % args

            else:
                msg = "searching for jobs with at least %i priority" % min_priority

            log(msg)
            for job in query:
                jobids.append(job.id)

    log("found %i jobs matching query" % len(jobids))

    if jobids and select:
        log("selecting one job from list")
        return random.choice(jobids)

    return jobids
# end select

if __name__ == '__main__':
    select()
