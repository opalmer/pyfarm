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

from sqlalchemy.engine.url import URL as _SQAUrl
from sqlalchemy.engine import create_engine
from sqlalchemy.exc import ArgumentError

from pyfarm.config.core import Loader
from pyfarm.config.errors import DBConfigError

class URL(_SQAUrl):
    def __str__(self):
        print "="*20, "TODO: needs fixes for sqlite urls"
        return super(URL, self).__str__()

class MissingConfigWaring(Warning):
    """
    used when a configuration is parsed by we cannot find a
    database entry
    """


class Database(Loader):
    REGEX_CONFIG = re.compile("^configs[.].+$")

    def __init__(self):
        Loader.__init__(self, "database.yml")
    # end __init__

    def engine(self, config_name):
        """returns an engine for `config_name`"""
        for url in self.get("configs.%s" % config_name):
            try:
                print create_engine(url)
            except ArgumentError:
                raise
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
                    config = self.get(config_name)

                except KeyError:
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


d = Database()
print d.engine("testing")