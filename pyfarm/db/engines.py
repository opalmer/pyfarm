# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

"""
prepares and configures the base engine
"""

from itertools import izip
from sqlalchemy import create_engine
from sqlalchemy.exc import OperationalError

from pyfarm.pref.simple import LoggingPreferences
from pyfarm.pref.database import DatabasePreferences
from pyfarm.logger import Logger
from pyfarm.errors import ConfigurationError

dbprefs = DatabasePreferences()
logprefs = LoggingPreferences()

CONFIGS = dbprefs.get('setup.config')
URLS = dbprefs.get('urls')
ECHO = logprefs.get('sqlalchemy.echo')
ECHO_POOL = logprefs.get('sqlalchemy.pool')

logger = Logger(__name__)

# zip up the configurations and the urls and
# iterate over them till we find one we can use
for config, url in izip(CONFIGS, URLS):
    engine = create_engine(url, echo=ECHO, echo_pool=ECHO_POOL)

    try:
        # test to see if we can use this config to connect to
        # the database
        connection = engine.connect()
        logger.info("connected to database using config: %s" % config)

        # even though sqlite only for testing we enable
        # a couple of features
        if engine.dialect.name == "sqlite":
            engine.engine.execute('pragma foreign_keys=ON')

        connection.close()
        break

    except OperationalError:
        logger.warning("failed to connect with config: %s" % config)
        continue

else:
    raise ConfigurationError(msg="failed to connect to database using any config")
