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
module for database specific preference handling
"""

import warnings

from pyfarm.datatypes.backports import OrderedDict
from pyfarm.preferences.core.baseclass import Preferences
from pyfarm.logger import Logger

logger = Logger(__name__)

class DatabasePreferences(Preferences):
    """
    specialized class for handling preferences related to
    the database
    """
    def __init__(self):
        super(DatabasePreferences, self).__init__(filename='database')
    # end __init__

    def _url(self, config):
        """
        returns the url for the given configuration name

        :type config: dict or string
        :param config:
            if the provided value for config is a string then use it
            to retrieve a configuration otherwise use it to construct
            the url

        :exception TypeError:
            raised if we got an unexpected type for config

        :exception ValueError:
            Raised if we are given inconsistent input for certain data.  As
            an example if a password is configured but a user name is not
            then we raise an exception
        """
        if isinstance(config, basestring):
            # load the connection information, skip
            # any keys that do not contain data
            config = self.get(config)

        # if we're not working with a dict then we can't
        # work with this data structure
        elif not isinstance(config, dict):
            raise TypeError(
                "expected a string or dictionary to pull the config from"
            )

        # retrieve the settings from the config
        driver = config.get('driver')
        engine = config.get('engine')
        dbname = config.get('name')
        dbuser = config.get('user')
        dbpass = config.get('pass')
        dbhost = config.get('host')
        dbport = config.get('dbport')

        # start of the url
        url = engine

        # if the driver is the ctypes form of psycopg2 then
        # we'll need to register it and swap out the named
        # driver for the url
        if driver == "psycopg2ct":
            try:
                from psycopg2ct import compat
                compat.register()

            except ImportError:
                logger.warning("failed to import psycopg2ct, skipping")
                raise

            else:
                driver = "psycopg2"

        # so long as we have a driver, add it to
        # the engine
        if driver is not None:
            url += "+%s" % driver

        url += "://"

        # if we are working with sqlite then stop here
        # and produce a warning
        if engine == "sqlite":
            warnings.warn(
                "sqlite is only supported for testing purposes",
                RuntimeWarning
            )
            return "%s/%s" % (url, dbhost)

        # password can't be provided without login
        if dbuser is None and dbpass is not None:
            raise ValueError("dbpass was provided but dbuser was was not")

        # setup user
        if dbuser is not None:
            url += dbuser

            # setup password
            if dbpass is not None:
                url += ":%s" % dbpass

        # port can't be provided without a host
        if dbport is not None and dbhost is None:
            raise ValueError("dbport was provided and a dbhost was not")

        if dbhost is not None:
            url += "@%s" % dbhost

            if isinstance(dbport, int):
                url += ":%s" % dbport

            elif dbport is not None:
                raise TypeError("expected an integer for the port")

        if dbname is None:
            raise ValueError("value not provided for dbname")
        else:
            url += "/%s" % dbname

        return url
    # end _url

    def get(self, key, **kwargs):
        """
        overrides :meth:`Preferences.get` to handle special cases for
        the database urls
        """
        # special handling to ensure that calls to both the old urls
        # lookup and the new 'urls' works equally
        if isinstance(key, basestring) and key.endswith("urls"):
            configs = OrderedDict()

            # iterate over all configurations and dbnames producing
            # urls to connect to using sqlalchemy
            for config_name, dbnames in self.get('setup.configs').iteritems():
                for dbname in dbnames:
                    try:
                        url = self._url(dbname)

                        config_data = configs.setdefault(
                            config_name, OrderedDict()
                        )
                        config_data[dbname] = url

                    # on key errors (missing configurations) log
                    # warning but continue on
                    except KeyError:
                        msg = "%s.%s does not have any " % (config_name, dbname)
                        msg += "configuration data"
                        logger.warning(msg)

                    # skip any import errors (let the underlying logic
                    # handle the warnings)
                    except ImportError:
                        continue

            return configs

        # in all other cases, call the underlying get() method
        else:
            return super(DatabasePreferences, self).get(key, **kwargs)
    # end get
# end DatabasePreferences