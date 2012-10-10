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
import threading
from itertools import ifilter

from pyfarm.db import contexts
from pyfarm.logger import Logger
from pyfarm.preferences import prefs
from pyfarm.utility import ScheduledRun
from pyfarm.db.tables import hosts as _hosts, frames as _frames, jobs as _jobs
from pyfarm.datatypes.enums import ACTIVE_HOSTS_FRAME_STATES,\
    ACTIVE_JOB_STATES, ACTIVE_FRAME_STATES

class Assignment(ScheduledRun, Logger):
    LOCK = threading.Lock()

    def __init__(self):
        Logger.__init__(self, self)
        ScheduledRun.__init__(self, prefs.get('master.assignment-interval'))
    # end __init__

    def run(self, force=False):
        start = time.time()

        with Assignment.LOCK:
            # check one last time before we attempt to run
            # the assignment
            if not self.shouldRun(force):
                self.warning("skipping assignment, lastrun < interval")
                return

            # retrieve all online hosts
            with contexts.Session(_hosts, close_connections=False) as hosts:
                online_hosts = hosts.query.filter(
                    _hosts.c.online == True
                ).all()

                if not online_hosts:
                    self.error("cannot continue, no hosts found online")
                    return

            # retrieve the frame(s) running on these hosts
            hostids = [host.id for host in online_hosts]
            with contexts.Session(_frames, close_connections=False) as frames:
                running_frames = frames.query.filter(
                    _frames.c.host.in_(hostids)
                ).filter(
                    _frames.c.state.in_(ACTIVE_HOSTS_FRAME_STATES)
                ).all()

            # find the base jobs for all of the frames we found
            # that are currently running
            jobids = set((frame.job for frame in running_frames))
            with contexts.Session(_jobs, close_connections=False) as jobs:
                running_jobs = jobs.query.filter(_jobs.c.id.in_(jobids)).all()

            args = (len(online_hosts), len(running_frames), len(running_jobs))
            self.debug("found %s online host(s) running %s frame(s) in %s job(s)" % args)

            # go over each running frame and add it's resource usage
            # to the host running it
            running_jobs = dict((job.id, job) for job in running_jobs)
            online_hosts = dict((host.id, host) for host in online_hosts)
            for frame in running_frames:
                job = running_jobs[frame.job]
                host = online_hosts[frame.host]
                host.cpus -= job.cpus
                host.ram_usage += frame.ram if frame.ram > job.ram else job.ram

            # filter out any hosts which do not have enough ram
            # or cpu space left
            free_hosts = list(
                ifilter(
                    lambda host: host.cpus > 0 and host.ram_usage < host.ram_total,
                    online_hosts.itervalues()
            ))
            self.debug("found %s host with resources left over" % len(free_hosts))

            run_hosts = {}
            with contexts.Session(_jobs, close_connections=False) as jobs:
                base_query = jobs.query.filter(
                    _jobs.c.state.in_(ACTIVE_JOB_STATES)
                )

                if not base_query.count():
                    self.error("cannot continue, no active jobs")
                    return

                # Find at least two jobs which match the host and should
                # not overflow the resources right off the bat.  We want to
                # retrieve two jobs here in case there are not enough frames
                # running on the first job to use all resources
                for host in free_hosts:
                    cpus = host.cpus
                    ram = host.ram_total - host.ram_usage
                    results = base_query.filter(
                        _jobs.c.cpus <= cpus
                    ).filter(
                        _jobs.c.ram < ram
                    ).order_by(
                        _jobs.c.priority
                    ).order_by(
                        _jobs.c.cpus
                    ).order_by(
                        _jobs.c.ram
                    )[-2:]

                    if not results:
                        self.warning("failed to find any jobs to run on %s" % host.hostname)
                        continue

                    run_hosts[host] = results

            with contexts.Session(_frames, close_connections=False) as frames:
                for host, jobs in run_hosts.iteritems():
                    # TODO:
                    # TODO: verify operation and results on db data
                    # TODO:
                    for job in reversed(jobs): # reverse so higher ram/cpus/etc come first
                        args = (job.id, host.hostname)
                        self.debug("selecting frame(s) from job %s for %s" % args)
                        enough_ram = host.ram_usage + job.ram < host.ram_total
                        enough_cpus = host.cpus - job.cpus > 0
                        index = 0

                        # construct the query we shold
                        query = frames.query.filter(
                            _frames.c.state.in_(ACTIVE_FRAME_STATES)
                        ).order_by(
                            _frames.c.priority
                        ).order_by(
                            _frames.c.attempts
                        )

                        while enough_ram and enough_cpus:
                            try:
                                frame = query[index]
                                index += 1

                            except IndexError:
                                break


                            # TODO:-------------------------------------------------
                            #  1. select frames from job
                            #  2. subtract job resources host (code below)
                            #  3. if resources left and frames exhausted
                            #     repeat 1. using secondary job
                            #  4. break
                            enough_ram = host.ram_usage + job.ram < host.ram_total
                            enough_cpus = host.cpus - job.cpus > 0
                            host.ram_usage += job.ram
                            host.cpus -= job.cpus

            # TODO:-------------------------------------------------
            # 1. mark each job being assigned as RUNNING (if changed)
            # 2. mark each frame as ASSIGN and tag it with the host
            # 3. send frame ids to host

        self.debug("elapsed for assignment %ss" % (time.time()-start))
# end Assignment

if __name__ == '__main__':
    assign = Assignment()
    assign.run()
