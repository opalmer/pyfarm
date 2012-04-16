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
Used to verify and submit jobs
'''

from __future__ import with_statement

import time
import inspect
import getpass
import logging

from twisted.python import log

from pyfarm import logger, datatypes, db
from pyfarm.preferences import prefs
from pyfarm.db import Transaction
from pyfarm.db import  tables


def validJobtype(jobtype):
    '''ensure the jobtype exists and return its module or raise an error'''
    mappings = prefs.get('jobtypes.mappings')
    if jobtype not in mappings:
        msg = "jobtype %s does not have a mapping " % jobtype
        msg += "in jobtypes.mappings, attempting to import directly"
        log.msg(msg)

        try:
            print jobtype
            module = __import__(jobtype, locals(), globals(), fromlist=['pyfarm.jobtypes'])

        except ImportError:
            raise ImportError("no such jobtype '%s'" % jobtype)

    else:
        modulename = mappings.get(jobtype)
        module = __import__(modulename, locals(), globals(), fromlist=['pyfarm.jobtypes'])

    log.msg("found module for %s jobtype: %s" % (jobtype, module.__file__))

    # check to make sure the Job class exists
    if not hasattr(module, 'Job') or not inspect.isclass(module.Job):
        raise AttributeError('%s jobtype missing the Job class' % jobtype)

    return module
# end validJobtype

def job(jobtype, start, end, by=1, data=None, environ=None, priority=500,
        software=None, ram=None, cpus=None, requeue=True, requeue_max=None,
        state=datatypes.State.QUEUED):
    '''
    Used to submit a new job with a range of frames.

    :param string jobtype:
        the name of the jobtype to pull from jobtypes.mappings

    :param integer start, end, by:
        the start, end, and byframe for the job

    :param integer state:
        the state to submit the job in

    :param dictionary data:
        Extra data to include with the job (scene path, additional flags, etc).
        This information is handled by the jobtype

    :param dictionary environ:
        environment to update the runtime environment with

    :param list software:
        software type or types (datatypes.Software) that are required to run
         the job

    :param integer ram:
        minimum free ram required to run the job

    :param integer cpus:
        minimum number of cpus required to run the job

    :param boolean requeue:
        if True failed frames will be requeued

    :param boolean requeue_max:
        max number of times a frame can be requeued

    :param integer priority:
        priority to submit with the job
    '''
    # ensure the jobtype exists and setup defaults
    validJobtype(jobtype)
    data = data or {}
    environ = environ or {}
    software = software or []
    requeue_max = requeue_max or prefs.get('jobtypes.defaults.requeue-max')

    # construct the data to commit
    data = {
        "jobtype" : jobtype, "state" : state,
        "start_frame" : start, "end_frame" : end, "by_frame" : by,
        "priority" : priority, "enviro" : environ,
        "data" : data, "user" : getpass.getuser(), "software" : software,
        "ram" : ram, "cpus" : cpus, "requeue_failed" : requeue,
        "requeue_max" : requeue_max
    }

    # commit data to table
    log.msg("submitting job for jobtype %s" % jobtype)

    # insert the job into the table
    time_start = time.time()
    log.msg("inserting %s job" % jobtype)
    table = tables.jobs
    insert = table.insert()
    results = insert.execute(data)
    jobid = int(results.last_inserted_ids()[0])
    log.msg("inserted job (id: %i)" % jobid)

    # insert the frames into the table
    count = 0
    table = tables.frames
    insert = table.insert()
    frameids = []

    args = (jobid, start, end, by, table)
    log.msg("inserting frames for job %i using range(%i, %i, %i) into %s" % args)
    for frame in range(start, end+1, by):
        data = {"jobid" : jobid, "frame" : frame, "priority" : priority}
        result = insert.execute(data)
        frameids.append(int(result.last_inserted_ids()[0]))
        count += 1

    time_end = time.time()
    args = (count, table.fullname, time_end-time_start)
    log.msg("inserted %i frames into %s (elapsed: %s)" % args)

    return jobid, frameids
# end job

def frames(jobid, start, end, by):
    '''
    used to submit a frame or frames to a job after submission

    :exception ValueError:
        raised if the priovided jobid does not exist
    '''
    frameids = []

    with Transaction(tables.frames, system="db.submit.frames") as trans:
        # ensure the parent job id exists in the database, retrieve
        # the priority if it does
        trans.log("ensuring job %i exists" % jobid)
        if not trans.query.filter_by(jobid=jobid).count():
            args = (jobid, tables.frames)
            raise ValueError("jobid %i does not exist in %s" % args)
        else:
            priority = jobs.priority(jobid)

        trans.log("iterating over frames")
        for i in range(start, end+1, by):
            exists = trans.query.filter_by(frame=i, jobid=jobid).count()

            # skip any frames that already exist
            if exists:
                trans.log(
                    "skipping frame %i, it already exists" % i,
                    level=logging.WARNING
                )
                continue

            data = {
                "jobid" : jobid, "priority" : priority, "frame" : i
            }
            insert = trans.table.insert()
            result = insert.execute(data)
            frameids.append(int(result.last_inserted_ids()[0]))

            print "%i does not exist" % i

    log.msg(
        "added %i frames to job %i" % (len(frameids), jobid),
        system="db.submit.frames"
    )
    return frameids
# end frames

if __name__ == '__main__':
#    job('mayatomr', 1, 10)
    frames(1, 1, 20, 1)
