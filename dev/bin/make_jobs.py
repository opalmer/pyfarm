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

JOB_COUNT = 20

import os
import site
import random

from twisted.python import log as _log

# initial setup
cwd = os.path.dirname(os.path.abspath(__file__))
trunk = os.path.abspath(os.path.join(cwd, "..", "..", "trunk"))
site.addsitedir(trunk)
os.chdir(trunk)

# pyfarm libs
from common import logger
from common.db import submit

def log(msg):
    _log.msg(msg, system="make-jobs")

# setup jobtypes
jobtypes = []
for filename in os.listdir("jobtypes"):
    if not filename.startswith("__") and filename.endswith(".py"):
        jobtypes.append(filename.split(".")[0])

log("jobtypes: %s" % jobtypes)

for i in range(JOB_COUNT):
    submit.job(
        random.choice(jobtypes), 1, 10, priority=random.randint(1, 1000),
        ram=random.randint(128, 4096), cpus=random.randint(1, 16)
    )
