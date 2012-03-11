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

from __future__ import with_statement

import copy

from sqlalchemy import func

import _tables
from common import logger, datatypes
from common.db.transaction import Transaction
from common.db.tables import jobs

def priority(jobid):
    '''
    returns the priority of the job

    :exception ValueError:
        raied if the job id does not exist
    '''
    return _tables.priority(jobs, jobid)
# end priority

def priority_stats():
    '''returns the priority stats of the current job table'''
    return _tables.priority_stats(jobs)
# end priority_stats

def active(priority=True):
    '''
    returns a list of running job id

    :param boolean priority:
        only the job(s) with the higest priority will be returned
    '''
    # only retrieve jobs which have the highest priority and
    # are currently active
    active_jobs = []
    with Transaction(jobs, system="query.jobs.active") as trans:
        max_priority = trans.session.query(func.max(trans.table.c.priority))
        query = trans.query.filter(jobs.c.priority == max_priority.first()[0])
        query = query.filter(jobs.c.state.in_(datatypes.ACTIVE_JOB_STATES))

        # at this points jobs will have the same priority so we add
        # jobs with the lowest number of running frames first
        for job in query.order_by(jobs.c.count_running):
            active_jobs.append(job)

        trans.log("found %i active jobs" % len(active_jobs))

    return active_jobs
# end running

def job(jobid):
    '''returns a job object for the given jobid'''
    with Transaction(jobs, system="query.jobs.job") as trans:
        query = trans.query.filter(jobs.c.id == jobid)
        result = query.first()
        return copy.deepcopy(result)
# end job
