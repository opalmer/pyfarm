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
Internal library used between modules to query tables
'''

import random
import logging
from sqlalchemy import func

from twisted.python import log as _log

from common import logger
from common.db.transaction import Transaction
from common.db import tables

def priority(table, jobid, frameid=None, frame=None):
    '''
    common function to retrieve the priority of either frames or
    jobs depending on the table
    '''
    if table == tables.jobs:
        query = {"id" : jobid}

        if frameid is not None or frame is not None:
            raise ValueError("cannot query frame or frameid from jobs table")

    elif table == tables.frames:
        query = {"jobid" : jobid}

        if frameid is not None:
            query.update(id=frameid)

        if frame is not None:
            query.update(frame=frame)

    with Transaction(table, system="query._tables.priority") as trans:
        msg = "retrieving priority for %i" % jobid
        if frameid is not None:
            msg += ".%i" % frameid
        elif frame is not None:
            msg += ".%i" % frame
        trans.log(msg)

        for result in trans.query.filter_by(**query):
            return int(result.priority)

    args = (table, query)
    raise ValueError("failed to retried results from %s using %s" % args)
# end priority

def priority_stats(table):
    '''returns the priority stats of the current job table'''
    with Transaction(table, system="query._tables.priority_stats") as trans:
        # query for min priority
        query = trans.session.query(func.min(trans.table.c.priority))

        # if we failed to retrieve the first query then all other will
        # fail so we return here
        if not query.count():
            return None, None, None

        p_min = int(query.first()[0])

        # query for max priority
        query = trans.session.query(func.max(trans.table.c.priority))
        p_max = int(query.first()[0])

        # query for average priority
        query = trans.session.query(func.avg(trans.table.c.priority))
        p_avg = int(query.first()[0])

        trans.log("p_min for %s: %s" % (trans.table, p_min))
        trans.log("p_max for %s: %s" % (trans.table, p_max))
        trans.log("p_avg for %s: %s" % (trans.table, p_avg))
        return p_min, p_max, p_avg
# end priority_stats

def select(table, jobid=None, min_priority=None, max_priority=None, select=True):
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
        kwargs.update(system="query._tables.select")
        _log.msg(msg, **kwargs)
    # end log

    if table == tables.jobs and jobid is not None:
        log("job id already provided, returning data", level=logging.WARNING)
        return jobid

# end select
