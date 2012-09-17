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
import sqlalchemy as sql

from pyfarm import jobtypes, utility
from pyfarm.logger import LoggingBaseClass
from pyfarm.preferences import prefs
from pyfarm.datatypes.enums import State
from pyfarm.db import tables, session, transaction, query

__all__ = ['Job', 'Frame']

# TODO: rewrite (currently very slow and error prone for large sets of frames)

class SubmitBase(LoggingBaseClass):
    def __init__(self):
        self.data = []
    # end __init__

    @property
    def count(self):
        return len(self.data)
    # end count

    def getPriority(self, priority):
        '''ensures the priority provided is within an allowable range'''
        if priority is None:
            return prefs.get('jobsystem.priority-default')

        elif isinstance(priority, int):
            return priority

        raise TypeError("invalid type provided to priority")
    # end getPriority

    def close(self, conn=None):
        '''closes the given connection and clears self.data'''
        if conn is not None:
            conn.close()
            session.ENGINE.dispose()

        del self.data[:]
    # end close
# end SubmitBase


class Frame(SubmitBase):
    '''class for adding frames to an existing job'''
    VALID_STATES = (
        State.PAUSED, State.QUEUED, State.DONE,
        State.FAILED
    )
    def __init__(self, jobid=None):
        super(Frame, self).__init__()
        self.jobid = jobid
        self.jobvalid = None

        if isinstance(jobid, int):
            self.jobvalid = query.jobs.exists(jobid)

        if isinstance(jobid, int) and not self.jobvalid:
            raise LookupError("%i is not a valid job id" % jobid)
    # end __init__

    def add(self, frame=None, jobid=None, priority=None, ram=None, cpus=None,
            state=State.QUEUED, dependencies=None):
        '''
        adds a frame to be committed to the database

        :param integer frame:
            the frame to add to the database

        :param integer jobid:
            the jobid to assign to this frame (if not provided to __init__)

        :param integer priority:
            the priority to assign the frame

        :param integer ram:
            how much ram this frame requires to run on a host

        :param integer cpus:
            how many cpus are required to run this frame

        :param integer state:
            The state to start the frame in.  Valid enum states are PAUSED,
            QUEUED, DONE, and FAILED

        :exception ValueError:
            raised if a jobid was not provided or the sate was invalid
        '''
        if jobid is None and self.jobid is None:
            msg = "you must either setup Frame() with a parent job id "
            msg += "or provide one to Frame.add()"
            raise ValueError(msg)

        # ensure the state of the submitted frame is valid
        if state not in self.VALID_STATES:
            raise ValueError("not a valid state for frame submission")

        # retrieve and ensure the job id is valid
        jobid = jobid or self.jobid
        if self.jobvalid is None:
            self.jobvalid = query.jobs.exists(jobid)

        if not self.jobvalid:
            raise LookupError("%i is not a valid job id" % jobid)

        # prepare to add the frame
        dependencies = dependencies or []
        priority = self.getPriority(priority)
        data = {
            "frame" : frame, "jobid" : jobid, "priority" : priority,
            "state" : state, "dependencies" : dependencies, "ram" : ram,
            "cpus" : cpus
        }

        # ensure we do not add the same frame twice
        if data in self.data:
            self.log(
                "frame %i for job %i has already been added, skipping" % (frame, jobid),
                level=logging.WARNING
            )
            return

        self.data.append(data)
    # end add

    def commit(self):
        '''
        Commits the frame(s) to the database.  Any frames that already exist
        at the given job id will be skipped

        :return:
            a dictionary of jobs and frame(s) added to each job
        '''
        if not self.data:
            self.log(
                "no frames to commit, stopping submission",
                level=logging.WARNING
            )
            return {}

        self.log("committing %i frames" % self.count, level=logging.INFO)

        # make sure the entries we attempting to commit
        # do not already exist in the database
        self.log("...searching for duplicate frames")
        search_clauses = []
        for frame in self.data:
            # constructs a query to search for the
            # specific frame in the database
            search_clauses.append(
                tables.frames.c.frame == frame['frame'] and \
                tables.frames.c.jobid == frame['jobid']
            )

        # retrieve entries from the database which match out query
        select = sql.select(tables.frames.c)
        query = select.where(sql.or_(*search_clauses))
        existing_frames = []

        conn = session.ENGINE.connect()
        for result in conn.execute(query):
            existing_frames.append((result.frame, result.jobid))

        # now that we have discovered which frames exist
        # find what data we should commit
        commit_data = []
        for data in self.data:
            frame, jobid = data['frame'], data['jobid']

            if (frame, jobid) in existing_frames:
                msg = "frame %i with jobid %i already " % (frame, jobid)
                msg += "exists, skipping"
                self.log(msg, level=logging.WARNING)
                continue

            commit_data.append(data)

        if not commit_data:
            self.log(
                "nothing to commit, no frames found in commit_data",
                level=logging.WARNING
            )
            self.close(conn)
            return {}

        with conn.begin():
            conn.execute(
                tables.frames.insert(),
                commit_data
            )

        self.close(conn)

        # now retrieve the results of these new entries
        search_clauses = []
        for frame in commit_data:
            search_clauses.append(
                tables.frames.c.frame == frame['frame'] and \
                tables.frames.c.jobid == frame['jobid']
            )

        added_frames = {}
        select = sql.select(tables.frames.c)
        query = select.where(sql.or_(*search_clauses))
        with transaction.Connection() as conn:
            for result in conn.execute(query):
                if result.jobid not in added_frames:
                    added_frames[result.jobid] = []

                added_frames[result.jobid].append(result.frame)

        return added_frames
    # end commit
# end Frame


class Job(SubmitBase):
    '''class for submitting multiple jobs as once'''
    VALID_STATES = (State.PAUSED, State.QUEUED, State.BLOCKED)

    def __init__(self):
        super(Job, self).__init__()
        self.data = []
    # end __init__

    @property
    def frame_count(self):
        '''returns the number of frames waiting to be added'''
        count = 0

        for job in self.data:
            start, end, by = job['start_frame'], job['end_frame'], job['by_frame']
            count += len(list(utility.framerange(start, end, by)))

        return count
    # end frame_count

    def add(self, jobtype, start, end, by=1, data=None, environ=None,
            dependencies=None, priority=None, software=None, ram=None, cpus=None,
            requeue=True, requeue_max=None, state=State.QUEUED):
        '''
        Used to submit a new job with a range of frames.

        :param string jobtype:
            the name of the jobtype to pull from jobtypes.mappings

        :param integer start, end, by:
            the start, end, and byframe for the job

        :param integer state:
            The state to submit the job in.  Valid state enums are PAUSED,
            QUEUED, and BLOCKED

        :param dictionary data:
            Extra data to include with the job (scene path, additional flags, etc).
            This information is handled by the jobtype

        :param dictionary environ:
            environment to update the runtime environment with

        :param list dependencies:
            other job ids we are dependent on

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

        :exception ValueError:
            raised if the submitted state is invalid
        '''
        # ensure the provided jobtype is valid before we
        # attempt to build data
        if jobtype not in jobtypes.jobtypes():
            raise NameError("%s is not a valid jobtype" % jobtype)

        # ensure the state of the submitted frame is valid
        if state not in self.VALID_STATES:
            raise ValueError("not a valid state for frame submission")

        # setup default data
        data = data or {}
        environ = environ or {}
        software = software or []
        requeue_max = requeue_max or prefs.get('jobtypes.defaults.requeue-max')
        priority = self.getPriority(priority)

        # construct the job data to commit
        jobdata = {
            "jobtype" : jobtype, "state" : state,
            "start_frame" : start, "end_frame" : end, "by_frame" : by,
            "priority" : priority, "enviro" : environ,
            "data" : data, "user" : getpass.getuser(), "software" : software,
            "ram" : ram, "cpus" : cpus, "requeue_failed" : requeue,
            "requeue_max" : requeue_max,
            "count_total" : len(xrange(start, end+1, by))
        }

        if dependencies:
            jobdata.update(dependencies=dependencies)

        self.data.append(jobdata)

        args = (os.linesep, pprint.pformat(jobdata))
        self.log("added pending job to commit: %s%s" % args)
    # end add

    def commit(self):
        '''
        commits all jobs and frames into their respective tables

        :return:
            a dictionary of jobs and frame(s) added to each job
        '''
        if not self.data:
            self.log(
                "no jobs to commit, stopping submission",
                level=logging.WARNING
            )
            return {}

        args = (self.count, self.frame_count)
        self.log(
            "preparing to submit %i job(s) and %i frame(s)" % args,
            level=logging.INFO
        )

        self.log(
            "...inserting %i new jobs into database" % self.count,
            level=logging.INFO
        )

        jobcount = 0
        conn = session.ENGINE.connect()

        framedata = []
        inserted_ids = []
        for job in self.data:
            with conn.begin():
                result = conn.execute(
                    tables.jobs.insert(),
                    [job]
                )
                jobid = int(result.last_inserted_ids()[0])
                inserted_ids.append(jobid)

            # prepare to insert frames into the database
            priority = job['priority']
            start, end, by = job['start_frame'], job['end_frame'], job['by_frame']
            jobcount += 1

            # prepare the frames for this job to be inserted
            # into the database
            for frame in utility.framerange(start, end, by):
                data = {
                    "frame" : frame, "jobid" : jobid, "priority" : priority,
                    "ram" : job['ram']
                }
                framedata.append(data)

        self.log("...inserted %i jobs" % jobcount)
        self.close(conn)

        # insert frames into the database
        frames = Frame()
        for data in framedata:
            frames.add(**data)

        committed_frames = frames.commit()
        count = 0
        for jobid, frames in committed_frames.items():
            count += len(frames)
        self.log("...inserted %i frames" % count)

        return committed_frames
    # end commit
# end Job

if __name__ == '__main__':
#    submit = Frame(590)
    submit = Job()
    submit.add('mayatomr', 1, 10)
    submit.add('mayatomr', 1, 10)
    print submit.commit()
    #    submit.log('test')
