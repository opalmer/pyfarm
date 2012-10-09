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
import sqlalchemy as sql

from pyfarm.preferences import prefs
from pyfarm.db.session import ENGINE
from pyfarm.logger import Logger
from pyfarm.datatypes.enums import State, DEFAULT_JOBTYPES, DEFAULT_GROUPS, DEFAULT_SOFTWARE

logger = Logger(__name__)

HOSTNAME_LENGTH = 255
IPV4_LENGTH = 16

# create global metadata object and bind the engine
metadata = sql.MetaData()
metadata.bind = ENGINE

hosts = sql.Table('pyfarm_hosts', metadata,
    sql.Column('id', sql.Integer, autoincrement=True, primary_key=True),
    sql.Column('hostname', sql.String(HOSTNAME_LENGTH), nullable=False),
    sql.Column('port', sql.Integer, nullable=False),
    sql.Column('master', sql.String(HOSTNAME_LENGTH), default=None),
    sql.Column('ip', sql.String(IPV4_LENGTH)),
    sql.Column('subnet', sql.String(IPV4_LENGTH)),
    sql.Column('os', sql.Integer),
    sql.Column('ram_total', sql.Integer),
    sql.Column('ram_usage', sql.Integer),
    sql.Column('swap_total', sql.Integer),
    sql.Column('swap_usage', sql.Integer),
    sql.Column('cpus', sql.Integer),
    sql.Column('online', sql.Boolean, nullable=False, default=True),
    sql.Column('groups', sql.PickleType, default=DEFAULT_GROUPS),
    sql.Column('software', sql.PickleType, default=DEFAULT_SOFTWARE),
    sql.Column('jobtypes', sql.PickleType, default=DEFAULT_JOBTYPES)
)

# MASTERS TABLE ATTRIBUTES
# queue - queue management and accepting new jobs
# assignment - sending jobs to hosts when enabled
masters = sql.Table('pyfarm_masters', metadata,
    sql.Column('id', sql.Integer, autoincrement=True, primary_key=True),
    sql.Column('hostname', sql.String(HOSTNAME_LENGTH), nullable=False),
    sql.Column('port', sql.Integer, nullable=False),
    sql.Column('ip', sql.String(IPV4_LENGTH)),
    sql.Column('subnet', sql.String(IPV4_LENGTH)),
    sql.Column('online', sql.Boolean, default=False),
    sql.Column('queue', sql.Boolean, default=True),
    sql.Column('assignment', sql.Boolean, default=True)
)

jobs = sql.Table('pyfarm_jobs', metadata,
    sql.Column('id', sql.Integer, autoincrement=True, primary_key=True),
    sql.Column('state', sql.Integer, default=0),
    sql.Column('priority', sql.Integer, default=prefs.get('jobsystem.priority-default')),

    # frame range declaration
    sql.Column('start_frame', sql.Integer),
    sql.Column('end_frame', sql.Integer),
    sql.Column('by_frame', sql.Integer, default=1),
    sql.Column('batch_frame', sql.Integer, default=1),

    sql.Column('time_submitted', sql.DateTime, default=datetime.datetime.now),

    # job setup
    # isolate - if True this job must run by itself (no other jobs on host)
    # cpus - number of cpus required to be free on the host
    # enviro - pickle of an environment dictionary
    sql.Column('cmd', sql.Text(convert_unicode=True), nullable=False),
    sql.Column('args', sql.PickleType, nullable=False),
    sql.Column('notes', sql.String(512, convert_unicode=True), default=""),
    sql.Column('environ', sql.PickleType),
    sql.Column('data', sql.PickleType),
    sql.Column('user', sql.String(256)),
    sql.Column('software', sql.PickleType, default=None),
    sql.Column('jobtype', sql.String(64)),
    sql.Column('ram', sql.Integer, default=None),
    sql.Column('cpus', sql.Integer, default=None),
    sql.Column('requeue_failed', sql.Boolean,
               default=prefs.get('jobtypes.defaults.requeue-failed')),
    sql.Column('requeue_max', sql.Integer,
               default=prefs.get('jobtypes.defaults.requeue-max'))
)

# FRAMES TABLE ATTRIBUTES
# host - host id currently running the frame
frames = sql.Table('pyfarm_frames', metadata,
    sql.Column('id', sql.Integer, autoincrement=True, primary_key=True),
    sql.Column('host', sql.Integer, sql.ForeignKey(hosts.c.id), default=None),
    sql.Column('jobid', sql.Integer, sql.ForeignKey(jobs.c.id)),
    sql.Column('priority', sql.Integer, default=prefs.get('jobsystem.priority-default')),
    sql.Column('host', sql.Integer, sql.ForeignKey(hosts.c.id), default=None),
    sql.Column('frame', sql.Integer),
    sql.Column('state', sql.Integer, default=State.QUEUED),
    sql.Column('attempts', sql.Integer, default=0),
    sql.Column('ram', sql.Integer, default=None),
    sql.Column('time_start', sql.DateTime, default=None),
    sql.Column('time_end', sql.DateTime, default=None),
    sql.Column('time_submitted', sql.DateTime, default=datetime.datetime.now),
    sql.Column('dependencies', sql.PickleType, default=[])
)

def init(rebuild=False):
    '''initializes the tables according the the preferences'''
    if rebuild or prefs.get('database.setup.rebuild'):
        logger.warning('dropping all tables before rebuilding')
        metadata.drop_all()

    metadata.create_all()
# end init
