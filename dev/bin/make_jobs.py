#!/usr/bin/env python
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
creates a random assortment of jobs in the database based on the current
setup
'''

import random

from twisted.python import log as _log

# pyfarm libs
from pyfarm.preferences import prefs
from pyfarm import logger, jobtypes
from pyfarm.db import submit

JOB_COUNT = 100

prefs.set('database.setup.close-connections', False)

def log(msg):
    _log.msg(msg, system="make-jobs")

typenames = jobtypes.jobtypes()
log("jobtypes: %s" % typenames)

submit_jobs = submit.Job()
test_hosts = ('localhost', 'foobar')

for i in xrange(JOB_COUNT+1):
    job_data = {
        'state' : random.choice(submit_jobs.VALID_STATES),
        'start_frame' : random.randint(1, 10),
        'end_frame' : random.randint(11, 20),
        'by_frame' : random.randint(1, 3),
        'jobtype' : random.choice(typenames),
        'ram' : random.randint(512, 4096),
        'cpus' : random.randint(1, 16),
        'command' : 'ping -c ${frame} %s' % random.choice(test_hosts)
    }
    submit_jobs.add(**job_data)

submit_jobs.commit()
log('DONE!')
