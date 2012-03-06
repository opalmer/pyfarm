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
Provides common mechanisms for querying job related information
'''

import random
from sqlalchemy import func

from twisted.python import log as _log

from common import logger
from common.db.transaction import Transaction
from common.db.tables import jobs, frames

def select(min_priority=None, max_priority=None, select=True):
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
        kwargs.update(system="query.jobs.select")
        _log.msg(msg, **kwargs)
    # end log

    jobids = []
    if min_priority is None and max_priority is None:
            highest_priority = priority_max()

            # find all jobs matching this priority (unless we did not find
            # any jobs)
            if highest_priority:
                for job in trans.query.filter_by(priority=highest_priority):
                    jobids.append(job.id)

                args = (len(jobids), highest_priority)
                log("found %i job(s) with priority %i" % args)

            else:
                log("no jobs found")

    if min_priority is not None:
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

def priority(jobid):
    '''
    returns the priority of the job

    :exception ValueError:
        raied if the job id does not exist
    '''
    with Transaction(jobs, system="query.jobs.priority") as trans:
        trans.log("retrieving priority for job %i" % jobid)
        for result in trans.query.filter_by(id=jobid):
            return int(result.priority)

    args = (jobid, jobs)
    raise ValueError("jobid %i does not exist in %s" % args)
# end priority

def priority_stats():
    '''returns the min priority of the current job table'''
    with Transaction(jobs, system="query.jobs.priority_stats") as trans:
        # query for min priority
        query = trans.session.query(func.min(trans.table.c.priority))
        p_min = query.first()[0]

        # query for max priority
        query = trans.session.query(func.max(trans.table.c.priority))
        p_max = query.first()[0]

        # query for average priority
        query = trans.session.query(func.avg(trans.table.c.priority))
        p_avg = int(query.first()[0])

        trans.log("p_min for %s: %s" % (trans.table, p_min))
        trans.log("p_max for %s: %s" % (trans.table, p_max))
        trans.log("p_avg for %s: %s" % (trans.table, p_avg))
        return p_min, p_max, p_avg
# end priority_min
