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
prepares and configures the base engine
'''

import itertools
from sqlalchemy import create_engine
from sqlalchemy.exc import OperationalError

from pyfarm.preferences import prefs
from pyfarm.logger import Logger
from pyfarm.errors import ConfigurationError

config = prefs.get('database.setup.config')
urls = prefs.get('database.urls')

logger = Logger(__name__)

# zip up the configurations and the urls and
# iterate over them till we find one we can use
zipconfig = itertools.izip(config, urls)
for config, url in zipconfig:
    engine = create_engine(
        url,
        echo=prefs.get('logging.sqlalchemy.echo'),
        echo_pool=prefs.get('logging.sqlalchemy.pool')
    )

    try:
        # test to see if we can use this config to connect to
        # the database
        engine.connect()
        logger.info("connected to database using config: %s" % config)
        break

    except OperationalError:
        logger.warning("failed to connect with config: %s" % config)
        continue

else:
    raise ConfigurationError(msg="failed to connect to database using any config")
