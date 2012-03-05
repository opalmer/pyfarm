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

import inspect
import getpass

from common import logger, datatypes, db
from common.preferences import prefs
from common.db import tables

from twisted.python import log

def validJobtype(jobtype):
    '''ensure the jobtype exists and return its module or raise an error'''
    mappings = prefs.get('jobtypes.mappings')
    if jobtype not in mappings:
        msg = "jobtype %s does not have a mapping " % jobtype
        msg += "in jobtypes.mappings, attempting to import directly"
        log.msg(msg)

        try:
            module = __import__('jobtypes.%s' % jobtype, fromlist=['jobtypes'])

        except ImportError:
            raise ImportError("no such jobtype '%s'" % jobtype)

    else:
        modulename = mappings.get(jobtype)
        module = __import__('jobtypes.%s' % modulename, fromlist=['jobtypes'])

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
    with db.Transaction(tables.jobs) as trans:
        insert = trans.insert()
        trans.log("inserting %s job" % jobtype)
        results = insert.execute(data)

    jobid = int(results.last_inserted_ids()[0])
    log.msg("successfully submitted %s job (id: %i)" % (jobtype, jobid))
    return jobid
# end job

def frame():
    '''
    used to submit a frame to the frames table once a host has
    picked up the work
    '''
    pass
# end frame


if __name__ == '__main__':
    job_id = job(
        'mayatomr', 1, 20, data={"scene" : "/tmp/scene.mb"}, ram=20, cpus=1
    )
