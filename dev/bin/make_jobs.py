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

import os
import random

from twisted.python import log as _log

# pyfarm libs
from pyfarm.preferences import prefs
from pyfarm import jobtypes
from pyfarm.logger import Logger
from pyfarm.db import submit
from pyfarm.datatypes.enums import State

JOB_COUNT = 100

logger = Logger(__name__)
prefs.set('database.setup.close-connections', False)

typenames = jobtypes.jobtypes()
logger.debug("jobtypes: %s" % typenames)

submit_jobs = submit.Job()

# create four jobs with more realistic test
# data
base_data = {
    'state' : State.QUEUED,
    'by_frame' : 1,
    'jobtype' : 'mayatomr',
    'ram' : 512,
    'cpus' : 4,
    'cmd' : 'Render',
    'args' : [
        '-r', 'mr', '-v', '5',
        '-s', '$frame', '-e', '$frame', '-rt', '$cpus',
        # TODO: provide path sep conversion on pyfarm's side
        '$root/projects/pyfarm/mayatomr/scenes/dynamics_v03.ma'
    ],
    'environ' : dict(os.environ)
}
job_data = base_data.copy()
job_data['start_frame'] = 1000
job_data['end_frame'] = 1100
job_data['priority'] = 500
submit_jobs.add(**job_data)
job_data = base_data.copy()
job_data['start_frame'] = 1101
job_data['end_frame'] = 1200
job_data['priority'] = 500
submit_jobs.add(**job_data)
job_data = base_data.copy()
job_data['start_frame'] = 1201
job_data['end_frame'] = 1300
job_data['priority'] = 750
submit_jobs.add(**job_data)
job_data = base_data.copy()
job_data['start_frame'] = 1301
job_data['end_frame'] = 1400
job_data['priority'] = 100
submit_jobs.add(**job_data)

submit_jobs.commit()
logger.debug('DONE!')
