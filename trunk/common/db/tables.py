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

import sqlalchemy as sql

from common import preferences

# create and connect to the engine
engine = sql.create_engine(preferences.DB_URL)
engine.echo = preferences.DB_ECHO

# create global metadata object and bind the engine
metadata = sql.MetaData()
metadata.bind = engine

# HOSTS TABLE ATTRIBUTES
# hold - if True the given client cannot accept jobs until hold is False again
# frames - csv list of frames current running (from frames.c.id)
# cpus - number of cpus on the system
# online - True if we are able to reach and communicate with the host.  This
#          value can only be set if:
#           - the host shuts down
#           - an operation times out and we are unable to ping the host
#             after an exception is raised
# software - csv list of software that the host can run
# jobtypes - csv list of jobtypes that the host can run
# os - the operating system of the host
#      0 - linux
#      1 - mac
#      2 - window
#      3 - other/unknown
hosts = sql.Table('pyfarm_hosts', metadata,
    sql.Column('id', sql.Integer, autoincrement=True, primary_key=True),
    sql.Column('hostname', sql.String(36)),
    sql.Column('ip', sql.String(16)),
    sql.Column('subnet', sql.String(16)),
    sql.Column('os', sql.Integer),
    sql.Column('ram_total', sql.Integer),
    sql.Column('ram_usage', sql.Integer),
    sql.Column('swap_total', sql.Integer),
    sql.Column('swap_usage', sql.Integer),
    sql.Column('cpu_count', sql.Integer),
    sql.Column('online', sql.Boolean),
    sql.Column('groups', sql.String(128), default='*'),
    sql.Column('software', sql.String(256), default="*"),
    sql.Column('hold', sql.Boolean, default=False),
    sql.Column('frames', sql.String(128), default="")
)

# create jobs table
jobs = sql.Table('pyfarm_jobs', metadata,
    sql.Column('id', sql.Integer, autoincrement=True, primary_key=True),
    sql.Column('state', sql.Integer, default=0),
    sql.Column('priority', sql.Integer, default=0),

    # frame range declaration
    sql.Column('start_frame', sql.Integer),
    sql.Column('end_frame', sql.Integer),
    sql.Column('by_frame', sql.Integer),

    # frame statistics
    sql.Column('count_success', sql.Integer, default=0),
    sql.Column('count_failed', sql.Integer, default=0),
    sql.Column('count_running', sql.Integer, default=0),
    sql.Column('frame_longest', sql.Float, default=0),
    sql.Column('frame_shortest', sql.Float, default=0),
    sql.Column('frame_average', sql.Float, default=0),

    # timers
    sql.Column('time_start', sql.Float),
    sql.Column('time_end', sql.Float),
    sql.Column('time_elapsed', sql.Float),

    # job setup
    # isolate - if True this job must run by itself (no other jobs on host)
    # cpus - number of cpus required to be free on the client
    # enviro - pickle of an environment dictionary
    sql.Column('enviro', sql.PickleType),
    sql.Column('user', sql.String(256)),
    sql.Column('software', sql.Integer),
    sql.Column('jobtype', sql.Integer),
    sql.Column('ram', sql.Integer),
    sql.Column('cpus', sql.Integer, default=-1),
    sql.Column('requeue_failed', sql.Boolean, default=False),
    sql.Column('requeue_max', sql.Integer)
    )

# create frames table
# uuid - uuid of job on client
frames = sql.Table('pyfarm_frames', metadata,
    sql.Column('id', sql.Integer, autoincrement=True, primary_key=True),
    sql.Column('parent_id', sql.Integer, sql.ForeignKey(jobs.c.id)),
    sql.Column('host', sql.Integer, sql.ForeignKey(hosts.c.id)),
    sql.Column('frame', sql.Integer),
    sql.Column('state', sql.Integer, default=0),
    sql.Column('attempts', sql.Integer, default=0),
    sql.Column('ram', sql.Integer),
    sql.Column('time_start', sql.Float),
    sql.Column('time_end', sql.Float),
    sql.Column('time_elapsed', sql.Float),
    sql.Column('uuid', sql.String(36))
)

def init():
    '''initializes the tables according the the preferences'''
    if preferences.DB_REBUILD:
        metadata.drop_all()

    metadata.create_all()
# end init
