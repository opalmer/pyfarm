#!/usr/bin/env python

'''
randomly assigns several jobs/frames to hosts
'''

import sys
import random
sys.path.append("../../lib/python")

from pyfarm.db import tables
from pyfarm.db.contexts import Session
from pyfarm.datatypes.enums import State, ACTIVE_FRAME_STATES, ACTIVE_JOB_STATES

with Session(tables.jobs) as jobs:
    all_jobs = jobs.query.filter(
        tables.jobs.c.state.in_(ACTIVE_JOB_STATES)
    ).all()

with Session(tables.frames) as frames:
    all_frames = frames.query.filter(
        tables.frames.c.state.in_(ACTIVE_FRAME_STATES)
    ).filter(
        tables.frames.c.jobid.in_((j.id for j in all_jobs))
    )

with Session(tables.hosts) as hosts:
    for host in hosts.query.all():
        assignments = []
        for i in xrange(random.randint(1, 3)):
            job = random.choice(all_jobs)
            frame = random.choice([f for f in all_frames if f.jobid == job.id])
            assignments.append((job.id, frame.id))

        host.jobs = assignments
        print "assigned %s to %s" % (host.jobs, host.hostname)
