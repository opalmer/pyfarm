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
import os

from sqlalchemy.engine.url import URL

from pyfarm.ext.config.core.loader import Loader
from pyfarm.config.core.errors import PreferencesError


class DBConfigError(PreferencesError):
    """raised when there's trouble either parsing or finding a db config"""


class DBConfig(Loader):
    """
    Custom class which allows for specific behaviors when working
    with the database.yml config.
    """
    REGEX_CONFIG = re.compile("^configs[.].+$")
    FILENAME = "database.yml"

    def url(self, config_name):
        """
        convenience method which returns the url for the given configuration
        name
        """
        return self.get("configs.%s" % config_name)

    def get(self, key, failobj=None):
        """
        Same as :meth:`Loader.get` except for database configurations
        which will return a a list of :class:`URL` object.
        """
        if not self.REGEX_CONFIG.match(key):
            return Loader.get(self, key, failobj=failobj)

        else:
            config_value = Loader.get(self, key, failobj=failobj)


            if config_value is None:
                raise DBConfigError("no configurations for `%s`" % key)

            try:
                config = self[config_value].copy()

            except KeyError:
                msg = "database configuration `%s` " % config_value
                msg += "does not exist, skipping"
                raise DBConfigError(msg)

            # expand any environment variables in the database name
            if "database" in config:
                config["database"] = os.path.expandvars(config["database"])

            # attempt to get the engine otherwise
            # fail (required key)
            try:
                engine = config.pop("engine")
            except KeyError:
                raise DBConfigError("missing `engine` in configs.%s" %
                                    config_value)

            driver_parts = [engine]
            driver = config.pop("driver", None)

            if driver is not None:
                driver_parts.append(driver)

            return str(URL("+".join(driver_parts), **config))
