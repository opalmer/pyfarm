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
from pyfarm.error import PreferencesError


class DBConfigError(PreferencesError):
    """raised when there's trouble either parsing or finding a db config"""


class DBConfig(Loader):
    """
    Custom class which allows for specific behaviors when working
    with the database.yml config.
    """
    REGEX_CONFIG = re.compile("^configs[.].+$")
    FILENAME = "database.yml"
    EXTENDED_CONFIGS = {}

    @classmethod
    def createConfig(cls, name, config_data):
        """
        Creates a new configuration that would be accessible by any instances
        of this class.  Mainly this is used for creating a configuration
        so things like :mod:`pyfarm.flaskapp` can function as is without
        modification.

        **Example:**

            >>> config = {"engine": "sqlite", "database": ":memory:"}
            >>> DBConfig.createConfig("unittest", config)

        :type name: str
        :param name:
            The name of the configuration to create.  Existing configurations
            by the same name will be overwritten.

        :type config_data: dict
        :param config_data:
            the data to add in the configuration
        """
        if not isinstance(config_data, dict):
            raise TypeError("`config_data` should be a dict")

        cls.EXTENDED_CONFIGS[name] = config_data.copy()

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
            # before doing anything else see if the configuration
            # we're looking for is part of `EXTENDED_CONFIGS`
            config_name = key.split(".")[-1]

            if config_name not in self.EXTENDED_CONFIGS:
                config_value = Loader.get(self, key, failobj=failobj)

                if config_value is None:
                    raise DBConfigError("no configurations for `%s`" % key)

                try:
                    config = self[config_value].copy()

                except KeyError:
                    msg = "database configuration `%s` " % config_value
                    msg += "does not exist, skipping"
                    raise DBConfigError(msg)

            else:
                config = self.EXTENDED_CONFIGS[config_name].copy()

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
