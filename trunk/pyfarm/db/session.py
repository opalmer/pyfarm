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

'''
Retains the global instance of the session maker object and returns new
sessions for use in transactions and other procedures
'''

import sqlalchemy as sql
from sqlalchemy import orm

from twisted.python import log

from pyfarm import logger, prefs

url = prefs.get('database.url')
config = prefs.get('database.setup.config')
ENGINE = sql.create_engine(
    url,
    echo=prefs.get('logging.sqlalchemy.echo'),
    echo_pool=prefs.get('logging.sqlalchemy.pool')
)
Session = orm.sessionmaker(bind=ENGINE)
log.msg("setup engine: %s, config: %s" % (ENGINE.name, config))
