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

import datetime
import threading
from itertools import chain, ifilter
from sqlalchemy import func

from pyfarm.logger import Logger
from pyfarm.preferences import prefs
from pyfarm.utility import ScheduledRun
from pyfarm.db import session, tables, contexts
from pyfarm.datatypes.enums import ACTIVE_JOB_STATES, ACTIVE_FRAME_STATES, State

class QueryHosts(Logger):
    '''
    class specific to retrieving and store host
    information at the time a
    '''
    def __init__(self):
        Logger.__init__(self, self)

        # find all running frames
        with contexts.Session(tables.frames) as frames:
            columns = tables.frames.c
            frames = frames.query.filter(
                columns.state.in_((State.RUNNING, State.ASSIGN)),
            ).all() or []
            self.__frames = dict((frame.id, frame) for frame in frames)

            if not frames:
                self.warning("did not find any running frames")

        # for every running frame get the base job id
        with contexts.Session(tables.jobs) as jobs:
            columns = tables.jobs.c
            jobs = jobs.query.filter(
                columns.id.in_((
                    frame.jobid for frame in self.__frames.itervalues()
                ))
            ).all() or []
            self.__jobs = dict((job.id, job) for job in jobs)

            if not jobs:
                self.debug("did not find any jobs with running frames")

        # get all online hosts
        with contexts.Session(tables.hosts) as hosts:
            hosts = hosts.query.filter(
                tables.hosts.c.online == True
            ).all() or []
            self.__hosts = dict((host.id, host) for host in hosts)

            if not hosts:
                self.warning("no online hosts found")
    # end __init__

    @property
    def online_hosts(self):
        if self.__online_hosts is None:
            with contexts.Session(tables.hosts) as trans:
                self.__online_hosts = trans.query.filter(
                    tables.hosts.c.online == True
                ).all() or []

        if not self.__online_hosts:
            self.warning("no online hosts found")

        return self.__online_hosts
    # end online_hosts

    def runningFrames(self, hostid):
        if hostid not in self.__cache:
            with contexts.Session(tables.frames) as trans:
                columns = tables.frames.c
                results = trans.query.filter(
                    columns.host == hostid
                ).filter(
                    columns.state.in_((State.RUNNING, State.ASSIGN))
                ).all() or []
                self.__cache[hostid] = results

        return self.__cache[hostid]
    # end runningFrames

    def get(self, ram, cpus):
        '''return a host that has at least this much ram and cpus free'''
        raise NotImplementedError("get() not yet implemented")
        for hostid, host in self.__hosts.iteritems():
            ram_left = host.ram_total - host.ram_usage

            # nothing to do if this host already does not
            # meet the requirements
            if ram_left < ram or host.cpu_count < cpus:
                continue

            frames = self.runningFrames(host.id)

            # if there are not any running frames for this host
            # then all we need to do is subtract how much
            # ram/cpu space the job is expected to use from the host
            if not frames and ram_left > ram and host.cpu_count >= cpus:
                host.cpu_count -= cpus
                host.ram_usage += ram
                return host

            # otherwise we have to retrieve all jobs
            # which are assigned to this host and subtract their cpu
            # and ram requirements from
            # TODO: average in frames which have a defined ramuse value to the
            # TODO:     job's expected ramuse (or perhaps override it with the avarage?)
            elif frames:
                continue
    # end get
# end QueryHosts

class Assignment(ScheduledRun, Logger):
    '''
    Assignment class which ensures that two assignment
    '''
    LOCK = threading.Lock()

    def __init__(self):
        Logger.__init__(self, self)
        ScheduledRun.__init__(self, prefs.get('master.assignment-interval'))
    # end __init__

    def getJobs(self):
        '''
        retrieve a single job which is the highest priority and is marked
        as active
        '''
        with contexts.Session(tables.jobs) as jobs:
            columns = tables.jobs.c

            # get all active jobs and return only those with the
            # highest priority
            active_jobs = jobs.query.filter(
                columns.state.in_(ACTIVE_JOB_STATES)
            ).all()
            max_priority = max(j.priority for j in active_jobs)
            return filter(lambda job: job.priority >= max_priority, active_jobs)
    # end getJobs

    def getFrames(self, jobs):
        with contexts.Session(tables.frames) as frames:
            columns = tables.frames.c

            valid_frames = []
            for job in jobs:
                # find frames that are active and match
                # our job id
                query = frames.query.filter(
                    columns.jobid == job.id
                ).filter(
                    columns.state.in_(ACTIVE_FRAME_STATES)
                )

                # additionally find frames that need to be
                # rerun if enabled
                if job.requeue_failed and job.requeue_max > 0:
                    query = query.filter(
                        columns.attempts < job.requeue_max
                    )

                results = query.all()
                if results is not None:
                    self.debug(
                        "found %s frames for job %s" % (len(results) , job.id)
                    )
                    valid_frames.append(results)
                else:
                    self.warning(
                        "failed to find any valid frames for %s" % job.id
                    )

        return valid_frames or None
    # end getFrames

    def getWork(self):
        '''
        return a dictionary of hosts and frame ids to assign to assign to
        each host
        '''
        jobs = self.getJobs()
        job_dict = dict((job.id, job) for job in jobs)

        if jobs is None:
            self.error("cannot continue, no jobs found to run")
            return

        frames = self.getFrames(jobs)
        if frames is None:
            self.error("cannot continue, no frames found to run")
            return


        # sort all frames by attempts then priority
        # TODO: sort on more than just priority
        frames = sorted(list(chain(*frames)), key=lambda f: f.priority)

        query = QueryHosts()
        for frame in frames:
            job = job_dict[frame.jobid]
            host = query.get(job.ram, job.cpus)
            if host:
                print "=== assign ",frame


    # end getWork

    def run(self, force=False):
        with Assignment.LOCK: # only want one thread at a time to have access
            # check one last time before we attempt to run
            # the assignment
            if not self.shouldRun(force):
                self.warning("skipping assignment, lastrun < interval")
                return

            self.info("running assignment")

            work = self.getWork()

            self.debug("finished assignment")
            self.lastrun = datetime.datetime.now()
    # end run
# end Assignment
