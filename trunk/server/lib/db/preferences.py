# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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

import os
import types
import ConfigParser

ETC = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "etc"))
CONFIG = os.path.join(ETC, "server.ini")

# ensure the preference file exists
if not os.path.isfile(CONFIG):
    error = "Missing server configuration file %s.  " %  CONFIG
    error += "Please create one from the template."
    raise IOError(error)

# read configuration
cfg = ConfigParser.ConfigParser()
cfg.read(CONFIG)

def get(section, option, default=None, typecast=str):
    '''
    attempt to get the option from the given section, return default
    if the option does not exist
    '''
    try:
        return typecast(cfg.get(section, option))

    except ConfigParser.NoOptionError:
        return default
# end get

def getUrl():
    '''returns the sql url based on preferences'''
    url = "%s" % DB_ENGINE

    # add the driver if it was provided in the preferences
    if DB_DRIVER:
        url += "+%s" % DB_DRIVER

    # the start of the url changes slightly for sqlite connections
    url += "://"
    if DB_ENGINE == "sqlite":
        url += "/"

    # server and login related preferences do not
    # apply to sqlite
    if DB_ENGINE == "sqlite":
        return url + DB_NAME

    # add username, password, and host
    url += "%s:%s@%s" % (DB_USER, DB_PASS, DB_HOST)

    # add port if it was provided
    if DB_PORT and isinstance(DB_PORT, types.IntType):
        url += ":%i" % DB_PORT

    # finally, add the database name
    url += "/%s" % DB_NAME

    return url
# end getUrl

# standard database login information
DB_CONFIG = cfg.get('DATABASE', 'config')
DB_HOST = get(DB_CONFIG, 'host') or 'localhost'
DB_PORT = get(DB_CONFIG, 'port', typecast=int)
DB_USER = get(DB_CONFIG, 'user')
DB_PASS = get(DB_CONFIG, 'pass')
DB_NAME = get(DB_CONFIG, 'name')
DB_ENGINE = get(DB_CONFIG, 'engine')
DB_DRIVER = get(DB_CONFIG, 'driver')
DB_URL = getUrl()
DB_REBUILD = cfg.getboolean('DATABASE', 'rebuild')
DB_ECHO = cfg.getboolean('DATABASE', 'echo')

# delete temp variables
del getUrl
del os, types, ConfigParser

if __name__ == '__main__':
    import pprint

    local = {}
    for key, value in locals().items():
        if key.isupper():
            local[key] = value

    pprint.pprint(local)
