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

'''constants to be used within the table module'''

import os as _os

from pyfarm.preferences import prefs as _prefs
from pyfarm.datatypes.enums import State as _state, SoftwareType as _softwaretype

# length of certain string fields
MAX_HOSTNAME_LENGTH = 255
MAX_SOFTWARE_LENGTH = 128
MAX_GROUP_LENGTH = 128
MAX_IPV4_LENGTH = 15
MIN_PORT = 1024
MAX_PORT = 65535
MAX_USERNAME_LENGTH = 255

# values defined by preferences
REQUEUE_MAX = _prefs.get('jobtypes.defaults.requeue-max')
REQUEUE_FAILED = _prefs.get('jobtypes.defaults.requeue-failed')
DEFAULT_PRIORITY = _prefs.get('jobsystem.priority-default')
DB_REBULD = _prefs.get('database.setup.rebuild')

# specifies all of the default table names
TABLE_PREFIX = _os.environ.get('PYFARM_TABLE_PREFIX') or "pyfarm_"
TABLE_MASTER = "%smasters" % TABLE_PREFIX
TABLE_FRAME = "%sframes" % TABLE_PREFIX

# host table names
TABLE_HOST = "%shosts" % TABLE_PREFIX
TABLE_HOST_SOFTWARE = "%s_software" % TABLE_HOST
TABLE_HOST_GROUP = "%s_group" % TABLE_HOST

# job tables
TABLE_JOB = "%sjobs" % TABLE_PREFIX
TABLE_JOB_DEPENDENCY = "%s_dependency" % TABLE_JOB
TABLE_JOB_SOFTWARE = "%s_software" % TABLE_JOB

# complete list of all table names
TABLE_NAMES  = sorted([
    value for key, value in globals().copy().iteritems() \
    if key.startswith("TABLE_") and key != "TABLE_PREFIX"
])

# generate a list of everything this module exports
__all__ = [
    key for key in globals().copy().iterkeys() if not key.startswith("_")
]
