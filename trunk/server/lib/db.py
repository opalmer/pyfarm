# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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

import preferences

# create and connect to the engine
engine = sql.create_engine(preferences.DB_URL)
engine.echo = True

# create global metadata object and bind the engine
metadata = sql.MetaData()
metadata.bind = engine


# create hosts table
hosts = sql.Table('pyfarm_hosts', metadata,
      sql.Column('uuid', sql.String(36), primary_key=True),
      sql.Column('hostname', sql.String(128)),
      sql.Column('ip', sql.String(16)),
      sql.Column('ram_max', sql.Integer),
      sql.Column('cpu_count', sql.Integer),
      sql.Column('online', sql.Boolean),
)

# if requested drop all tables that exist
if preferences.DB_REBUILD:
    metadata.drop_all()

metadata.create_all()