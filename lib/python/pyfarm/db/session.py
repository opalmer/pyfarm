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

import logging
from itertools import izip

import sqlalchemy
from sqlalchemy import orm

from twisted.python import log

from pyfarm.preferences import prefs
from pyfarm import errors

configs = prefs.get('database.setup.config')
urls = prefs.get('database.urls')

for config, url in izip(configs, urls):
    try:
        ENGINE = sqlalchemy.create_engine(
            url,
            echo=prefs.get('logging.sqlalchemy.echo'),
            echo_pool=prefs.get('logging.sqlalchemy.pool')
        )
        Session = orm.sessionmaker(bind=ENGINE)
        log.msg("setup engine: %s, config: %s" % (ENGINE.name, config))

        # if the session was setup properly then
        # we don't need to move onto the next possible
        # configuration
        break

    except Exception, error:
        log.msg(
            'failed using %s for config: %s' % (config, error),
            level=logging.WARNING
        )

else:
    raise errors.DatabaseError(
        "failed to find a valid configuration in %s" % configs
    )
