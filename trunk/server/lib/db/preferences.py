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
import site
import types

cwd = os.path.abspath(os.path.dirname(__file__))
root = os.path.abspath(os.path.join(cwd, "..", "..", ".."))
package = os.path.abspath(os.path.join(cwd, "..", ".."))
site.addsitedir(root)

# setup and load preferences object
import common.preferences as comprefs
prefs = comprefs.Preferences(root, package)
prefs.addPackage('database')

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

DB_CONFIG = prefs.get('DATABASE', 'config')
DB_HOST = prefs.get(DB_CONFIG, 'host', default='localhost')
DB_PORT = prefs.getint(DB_CONFIG, 'port')
DB_USER = prefs.get(DB_CONFIG, 'user')
DB_PASS = prefs.get(DB_CONFIG, 'pass')
DB_NAME = prefs.get(DB_CONFIG, 'name')
DB_ENGINE = prefs.get(DB_CONFIG, 'engine')
DB_DRIVER = prefs.get(DB_CONFIG, 'driver')
DB_URL = getUrl()
DB_REBUILD = prefs.getboolean('DATABASE', 'rebuild')
DB_ECHO = prefs.getboolean('DATABASE', 'echo')

if __name__ == '__main__':
    comprefs.debug(locals())
