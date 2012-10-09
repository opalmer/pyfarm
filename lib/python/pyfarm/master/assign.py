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

import time
import datetime
import threading
from itertools import chain

from pyfarm.logger import Logger
from pyfarm.preferences import prefs
from pyfarm.utility import ScheduledRun
from pyfarm.db import session, tables, contexts
from pyfarm.datatypes.enums import ACTIVE_JOB_STATES, ACTIVE_FRAME_STATES, State

class Assignment(ScheduledRun, Logger):
    '''
    Assignment class which ensures that two assignment
    '''
    LOCK = threading.Lock()

    def __init__(self):
        Logger.__init__(self, self)
        ScheduledRun.__init__(self, prefs.get('master.assignment-interval'))
        self.__jobstates = ",".join([State.get(i) for i in ACTIVE_JOB_STATES])
        self.__framestates = ",".join([State.get(i) for i in ACTIVE_FRAME_STATES])
    # end __init__

    def getJobs(self, hosts):
        '''
        retrieve a single job which is the highest priority and is marked
        as active
        '''
        results = {}
        start = time.time()
        self.debug("searching for jobs")

        with contexts.Session(tables.jobs) as jobs:
            for host in hosts:
                cpus = host.cpus
                ram = host.ram_total - host.ram_usage
                matching_jobs = jobs.query.filter(
                    tables.jobs.c.state.in_(ACTIVE_JOB_STATES)
                ).filter(
                    tables.jobs.c.cpus <= cpus
                ).filter(
                    tables.jobs.c.ram <= ram
                ).order_by(
                    tables.jobs.c.priority
                )

                matching_jobs = matching_jobs[-len(hosts):]
                args = (len(matching_jobs), host.hostname, len(hosts))
                self.debug("found %s possible jobs for %s (limit: %s)" % args)

                if not matching_jobs:
                    continue
                else:
                    results[host] = matching_jobs

        self.debug("found jobs in %ss" % (time.time()-start))
        return results or None
    # end getJobs

    def getFrames(self, jobdata):
        start = time.time()
        self.debug("searching for frames in states (%s)" % self.__framestates)
        results = {}
        with contexts.Session(tables.frames) as frames:
            columns = tables.frames.c

            for host, jobs in jobdata.iteritems():
                for job in jobs:
                    valid_frames = frames.query.filter(
                        columns.jobid == job.id
                    )

                    if job.requeue_failed and job.requeue_max > 0:
                        valid_frames = valid_frames.filter(
                            columns.state.in_((State.FAILED, State.QUEUED))
                        )
                    else:
                        valid_frames = valid_frames.filter(
                            columns.state == State.QUEUED
                        )

#                    valid_frames = valid_frames.order_by(
#                        columns.priority
#                    )

                    valid_frames = valid_frames[-host.cpus:] or []

                    if valid_frames and host not in results:
                        results[host] = []

                    results[host].extend(valid_frames)

        args = (len(results.keys()), (time.time()-start))
        self.debug("found frames for %s hosts in %s" % args)
        return results or None
    # end getFrames

    def getHosts(self):
        start = time.time()
        with contexts.Session(tables.hosts) as hosts:
            hosts = hosts.query.filter(
                tables.hosts.c.online == True
            ).all()

            # nothing to do here if there are not
            # any hosts online
            if hosts is None:
                return

        # retrieve all jobs running on the online hosts
        with contexts.Session(tables.jobs) as jobs:
            running_jobs = jobs.query.filter(
                tables.jobs.c.id.in_(
                    (job for job, frame in chain(*( host.jobs for host in hosts )))
                )
            ).all()

        # TODO: average/estimate required ram based off of frame + job ram estimate?
        jobs = dict(((job.id, job) for job in running_jobs))
        valid_hosts = []
        for host in hosts:
            for job in (jobs[job_id] for job_id, frame_id in host.jobs):
                host.cpus -= job.cpus
                host.ram_usage += job.ram

                if host.cpus <= 0:
                    self.debug("host %s does not have enough cpus" % host.hostname)
                    break

                elif host.ram_total <= host.ram_usage:
                    self.debug("host %s does not have enough ram" % host.hostname)
                    break

            else:
                valid_hosts.append(host)

        results = valid_hosts or []
        self.debug("got %s hosts in %ss" % ((len(results), time.time()-start)))
        return results or None
    # end getHosts

    def getWork(self):
        '''
        return a dictionary of hosts and frame ids to assign to assign to
        each host
        '''
        hosts = self.getHosts()
        if hosts is None:
            self.error("cannot continue, failed to find any hosts for assignment")
            return

        # search for valid jobs
        jobdata = self.getJobs(hosts)
        job_dict = dict((job.id, job)for job in chain(*jobdata.itervalues()))
        if jobdata is None:
            self.error("cannot continue, no jobs found to run")
            return

        # search for valid frames in those jobs
        frames = self.getFrames(jobdata)
        if frames is None:
            self.error("cannot continue, no frames found to run")
            return

#        print jobdata.keys()
        for host, frames in frames.iteritems():
#            if host.cpus <= 0 or host.:
#                continue

            for frame in sorted(frames, key=lambda frame: frame.priority, reverse=True):
                job = job_dict[frame.jobid]
                print host.hostname, host.cpus

                if host.cpus - job.cpus == 0:
                    print 'no cpus',host.hostname
                    break
#                    printor \
#                    host.ram_usage + job.ram > host.ram_total:
#                    break

                print host.hostname, frame
                host.cpus -= job.cpus
                host.ram_usage += job.ram

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
