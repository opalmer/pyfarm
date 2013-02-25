# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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

"""
module for database specific preference handling
"""

import warnings

from ordereddict import OrderedDict
from pyfarm.preferences.base.baseclass import Preferences
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
            try:
                config = self.get(config)

            except KeyError:
                return None

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
                return

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
            return ["%s/%s" % (url, dbhost)]

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
            url += "%/s"

        return url
    # end _url

    def get(self, key, **kwargs):
        if isinstance(key, basestring) and key.endswith("urls"):
            configs = OrderedDict()

            # iterate over all configurations and dbnames producing
            # urls to connect to using sqlalchemy
            for config_name, dbnames in self.get('setup.configs').iteritems():
                for dbname in dbnames:
                    url = self._url(dbname)
                    if url is None:
                        msg = "%s.%s does not have any " % (config_name, dbname)
                        msg += "configuration data"
                        logger.warning(msg)

            return configs
        else:
            return super(DatabasePreferences, self).get(key, **kwargs)
    # end get
# end DatabasePreferences