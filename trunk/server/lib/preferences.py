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

cwd = os.path.abspath(os.path.dirname(__file__))
root = os.path.abspath(os.path.join(cwd, "..", ".."))
package = os.path.abspath(os.path.join(cwd, ".."))
site.addsitedir(root)

# setup and load preferences object
import common.preferences as comprefs
prefs = comprefs.Preferences(root, package)
prefs.addRoot('common')
prefs.addPackage('server')

PORT = prefs.getint('NETWORK', 'port')
RESTART_ENABLED = prefs.getboolean('RESTART', 'enabled')
RESTART_TIMEOUT = prefs.getint('RESTART', 'wait')

if __name__ == '__main__':
    comprefs.debug(locals())
