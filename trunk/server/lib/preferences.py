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
import ConfigParser

MODULE_CONFIG = "server.ini"
GLOBAL_CONFIG = "common.ini"
CWD = os.path.dirname(__file__)
ETC = os.path.abspath(os.path.join(CWD, "..", "etc"))
ETC_GLOBAL = os.path.abspath(os.path.join(CWD, "..", "..", "etc"))
CONFIG = os.path.join(ETC, MODULE_CONFIG)
CONFIG_GLOBAL = os.path.join(ETC_GLOBAL, GLOBAL_CONFIG)

# ensure the preference files exists
if not os.path.isfile(CONFIG_GLOBAL):
    raise IOError("missing global configuration %s" % CONFIG_GLOBAL)

if not os.path.isfile(CONFIG):
    raise IOError("missing server configuration %s" % CONFIG)

# read configuration
cfg = ConfigParser.ConfigParser()
cfg.read(CONFIG)

# establish global preferences
PORT = cfg.getint('NETWORK', 'port')

# delete temp variables
del cfg
del os, ConfigParser

if __name__ == '__main__':
    import pprint

    local = {}
    for key, value in locals().items():
        if key.isupper():
            local[key] = value

    pprint.pprint(local)
