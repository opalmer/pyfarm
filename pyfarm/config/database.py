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

import os
from warnings import warn

from sqlalchemy.engine.url import URL

try:
    import simplejson as json
except ImportError:
    import json

from pyfarm.ext.config.core.loader import Loader
from pyfarm.error import (PreferencesError, PreferencesNotFoundError,
                          DBConfigError)
from pyfarm.warning import DBConfigWarning


class DBConfig(Loader):
    """
    Custom class which allows for specific behaviors when working
    with the database.yml config.
    """
    FILENAME = "database.yml"
    PREPEND_CONFIGS = []

    def __init__(self, *args, **kwargs):
        try:
            Loader.__init__(self, *args, **kwargs)

        # if we can't find a database.yml file, start with the template
        # then load configuration data from the environment
        except PreferencesNotFoundError:
            Loader.__init__(self, filename="database.yml.template")

        if not self.get("config_order"):
            if os.environ.get("SPHINX_BUILD") == "true":
                self.insertConfig("sphinx",
                                  {"engine": "sqlite", "database": ":memory:"})
                warn("building sphinx, using sqlite", DBConfigWarning)
            else:
                raise DBConfigError(
                    "`config_order` was either not present or empty")

    @classmethod
    def insertConfig(cls, name, config_data):
        """
        Creates a new configuration to use.

        :type name: str
        :param name:
            The name of the configuration to create.  Existing configurations
            by the same name will be overwritten.

        :type config_data: dict
        :param config_data:
            the data to add in the configuration
        """
        data = (name, config_data.copy())
        if data not in cls.PREPEND_CONFIGS:
            cls.PREPEND_CONFIGS.insert(0, data)

    def url(self, config_name=None):
        """
        convenience method which returns the url for the given configuration
        name
        """
        if config_name is None:
            config_name = self.get("config_order")[0]

        try:
            if config_name not in self:
                raise KeyError

            config = self.get(config_name).copy()

        except KeyError:
            msg = "database configuration `%s` " % config_name
            msg += "does not exist"
            raise DBConfigError(msg)

        # expand any environment variables in the database name
        if "database" in config:
            config["database"] = os.path.expandvars(config["database"])

        # attempt to get the engine otherwise
        # fail (required key)
        try:
            engine = config.pop("engine")
        except KeyError:
            raise DBConfigError("missing `engine` in %s" % config_name)

        driver_parts = [engine]
        driver = config.pop("driver", None)

        if driver is not None:
            driver_parts.append(driver)

        return str(URL("+".join(driver_parts), **config))

    def urls(self):
        """returns a list of urls to use when attempting to connect"""
        results = []
        for config_name in self.get("config_order", []):
            try:
                results.append(self.url(config_name))
            except DBConfigError:
                warn("configuration does not exist for `%s`" % config_name,
                     DBConfigWarning)

        return results

    def get(self, key, failobj=None):
        """
        Wrapper around the standard :func:`Loader.get` except we
        ensure that we pickup any changes to changes in :attr:`PREPEND_CONFIGS`
        """
        # Check to see if we're missing anything from PREPEND_CONFIGS.  It's
        # not an exact process but should ensure new entries are properly
        # added.
        for name, data in self.PREPEND_CONFIGS:
            if name not in self.data:
                self.data[name] = data
                self.data["configs"][name] = name
                self.data["config_order"].insert(0, name)

        return Loader.get(self, key, failobj=failobj)