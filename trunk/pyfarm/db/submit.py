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

import os
import getpass
import logging
import pprint

from pyfarm import logger, datatypes, jobtypes, utility
from pyfarm.preferences import prefs
from pyfarm.db import tables, session, transaction

class Job(logger.LoggingBaseClass):
    '''class for submitting multiple jobs as once'''
    def __init__(self):
        # stores frame and job information to submit
        self.jobs = []
    # end __init__

    @property
    def job_count(self):
        '''returns the number of jobs waiting to be added'''
        return len(self.jobs)
    # end job_count

    @property
    def frame_count(self):
        '''returns the number of frames waiting to be added'''
        count = 0

        for job in self.jobs:
            start, end, by = job['start_frame'], job['end_frame'], job['by_frame']
            count += len(list(utility.framerange(start, end, by)))

        return count
    # end frame_count

    def add(self, jobtype, start, end, by=1, data=None, environ=None,
            priority=500, software=None, ram=None, cpus=None, requeue=True,
            requeue_max=None, state=datatypes.State.QUEUED):
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

        :exception NameError:
            raised if the provided jobtype does not exist
        '''
        # ensure the provided jobtype is valid before we
        # attempt to build data
        if jobtype not in jobtypes.jobtypes():
            raise NameError("%s is not a valid jobtype" % jobtype)

        # setup default data
        data = data or {}
        environ = environ or {}
        software = software or []
        requeue_max = requeue_max or prefs.get('jobtypes.defaults.requeue-max')

        # construct the job data to commit
        jobdata = {
            "jobtype" : jobtype, "state" : state,
            "start_frame" : start, "end_frame" : end, "by_frame" : by,
            "priority" : priority, "enviro" : environ,
            "data" : data, "user" : getpass.getuser(), "software" : software,
            "ram" : ram, "cpus" : cpus, "requeue_failed" : requeue,
            "requeue_max" : requeue_max
        }
        self.jobs.append(jobdata)

        args = (os.linesep, pprint.pformat(jobdata))
        self.log("added pending job to commit: %s%s" % args)
    # end add

    def commit(self):
        '''commits all jobs and frames into their respective tables'''
        if not self.jobs:
            self.log(
                "no jobs to commit, stopping submission",
                level=logging.WARNING
            )
            return

        args = (self.job_count, self.frame_count)
        self.log(
            "preparing to submit %i job(s) and %i frame(s)" % args,
            level=logging.INFO
        )

        self.log(
            "...inserting %i new jobs into database" % self.job_count,
            level=logging.INFO
        )

        frames = []
        jobcount = 0
        conn = session.ENGINE.connect()

        for job in self.jobs:
            with conn.begin():
                result = conn.execute(
                    tables.jobs.insert(),
                    [job]
                )
                jobid = int(result.last_inserted_ids()[0])

            # prepare to insert frames into the database
            priority = job['priority']
            start, end, by = job['start_frame'], job['end_frame'], job['by_frame']
            jobcount += 1

            # iterate over every frame and generate data to insert into
            # the database
            for frame in utility.framerange(start, end, by):
                frames.append(
                    {"jobid" : jobid, "frame" : frame, "priority" : priority}
                )

        self.jobs = []
        self.log("...inserted %i jobs" % jobcount)
        self.log(
            "...inserting %i new frames into the database" % len(frames),
            level=logging.INFO
        )

        with conn.begin():
            result = conn.execute(tables.frames.insert(),frames)
            self.log("...inserted %i frames" % result._rowcount)

        self.log("...done", level=logging.INFO)

        # close the connection and dispose of the connection to the
        # database
        conn.close()
        session.ENGINE.dispose()
    # end commit
# end Job

if __name__ == '__main__':
    submit = Job()
#    submit.job('mayatomr', 1, 10)
#    submit.job('mayatomr', 11, 20)
    submit.commit()
