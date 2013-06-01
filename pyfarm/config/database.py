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

import re
from warnings import warn

from sqlalchemy.engine.url import URL
from sqlalchemy.engine import create_engine
from sqlalchemy.exc import OperationalError

from pyfarm.config.core import Loader
from pyfarm.config.core.errors import DBConfigError
from pyfarm.config.core.warning import MissingConfigWaring, SQLConnectionWarning


class DBConfig(Loader):
    """
    Custom class which allows for specific behaviors when working
    with the database.yml config.
    """
    REGEX_CONFIG = re.compile("^configs[.].+$")
    FILENAME = "database.yml"

    def engines(self, config_name, test=True):
        """
        Generator which yields all engines for the provided configuration.

        :param string config_name:
            The configuration name to use.  Using `production` would search
            database configurations matching `configs.production`.

        :param bool test:
            if True then iterate over each configuration but only
            yield those that we can successfully connect to
        """
        for url in self.get("configs.%s" % config_name):
            engine = create_engine(url)

            if test:
                try:
                    engine.connect()

                except OperationalError:  # TODO: add warning logger
                    warn(
                        "failed to connect to %s" % str(url),
                        SQLConnectionWarning
                    )
                    continue

            yield engine
    # end engines

    def engine(self, config_name, test=True):
        """
        convenience method which returns a single engine from :meth:`engines`
        """
        for engine in self.engines(config_name, test=test):
            return engine
    # end engine

    def get(self, key, failobj=None):
        """
        Same as :meth:`Loader.get` except for database configurations
        which will return a a list of :class:`URL` object.
        """
        value = Loader.get(self, key, failobj=failobj)

        if self.REGEX_CONFIG.match(key):
            configs = [value] if isinstance(value, basestring) else value
            value = []

            for config_name in configs:
                try:
                    config = self[config_name]

                except KeyError:  # TODO: add warning logger
                    msg = "database configuration `%s` " % config_name
                    msg += "does not exist, skipping"
                    warn(msg, MissingConfigWaring)

                else:
                    # attempt to get the engine otherwise
                    # fail (required key)
                    try:
                        engine = config.pop("engine")
                    except KeyError:
                        raise DBConfigError(
                            "missing `engine` in configs." % config_name
                        )

                    driver_parts = [engine]
                    driver = config.pop("driver", None)

                    if driver is not None:
                        driver_parts.append(driver)

                    # TODO: engine does NOT properly handle sqlite urls

                    value.append(URL("+".join(driver_parts), **config))

        return value
    # end get
# end Database
