# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
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
import psutil

cwd = os.path.abspath(os.path.dirname(__file__))
root = os.path.abspath(os.path.join(cwd, "..", ".."))
package = os.path.abspath(os.path.join(cwd, ".."))
site.addsitedir(root)

# setup and load preferences object
import common.preferences
prefs = common.preferences.Preferences(root, package)
prefs.addRoot('common')
prefs.addPackage('client')

CPU_COUNT = psutil.NUM_CPUS
MAX_JOBS = int(prefs.getfloat('PROCESSING', 'cpu_mult') * CPU_COUNT)
PRINT_OUTPUT = prefs.getboolean('LOGGING', 'print')
TIMESTAMP = prefs.get('LOGGING', 'timestamp')
CLIENT_PORT = prefs.getint('NETWORK', 'client_port')
PATHS_ENV = prefs.getenvlist('PATHS', 'environment')
PATHS_LIST = prefs.getlist('PATHS', 'list')
LOG_STDOUT = prefs.getboolean('LOGGING', 'stdout')
LOG_FILE = prefs.getboolean('LOGGING', 'file')
SHUTDOWN_ENABLED = prefs.getboolean('SHUTDOWN', 'enabled')
RESTART_ENABLED = prefs.getboolean('RESTART', 'enabled')
RESTART_DELAY = prefs.getint('RESTART', 'delay')
MULTICAST_PORT = prefs.getint('NETWORK', 'multicast_port')

if __name__ == '__main__':
    common.preferences.debug(locals())
