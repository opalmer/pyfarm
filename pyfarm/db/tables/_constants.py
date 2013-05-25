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

"""constants to be used within the table module"""

import os as _os

from pyfarm.datatypes.enums import State as _state
from pyfarm.pref.jobtypes import JobTypePreferences
from pyfarm.pref.simple import JobSystemPreferences
from pyfarm.pref.database import DatabasePreferences

# preference setup
jtprefs = JobTypePreferences()
jsprefs = JobSystemPreferences()
dbprefs = DatabasePreferences()

# length of certain string fields
MAX_HOSTNAME_LENGTH = 255
MAX_SOFTWARE_LENGTH = 128
MAX_GROUP_LENGTH = 128
MAX_IPV4_LENGTH = 15
MIN_PORT = 1024
MAX_PORT = 65535
MAX_USERNAME_LENGTH = 255

# frame/job starting/stopping states
FRAME_STATE_START = (_state.RUNNING, )
FRAME_STATE_STOP = (_state.DONE, _state.FAILED)
JOB_STATE_START = (_state.RUNNING, )
JOB_STATE_STOP = (_state.DONE, _state.FAILED)

# active state information
ACTIVE_HOSTS_FRAME_STATES = (_state.RUNNING, _state.ASSIGN)
ACTIVE_JOB_STATES = (_state.QUEUED, _state.RUNNING)
ACTIVE_FRAME_STATES = (_state.QUEUED, _state.FAILED)

# values defined by preferences
REQUEUE_MAX = jtprefs.get('defaults.requeue-max')
REQUEUE_FAILED = jtprefs.get('defaults.requeue-failed')
DEFAULT_PRIORITY = jsprefs.get('priority-default')
DB_REBULD = dbprefs.get('setup.rebuild')
JOB_QUERY_FRAME_LIMIT = jsprefs.get('job-query-frame-limit')

# specifies all of the default table names
TABLE_PREFIX = _os.environ.get('PYFARM_TABLE_PREFIX') or "pyfarm_"
TABLE_F2_DEPENDENCIES = "%sf2f_dependency" % TABLE_PREFIX
TABLE_J2J_DEPENDENCIES = "%sj2j_dependency" % TABLE_PREFIX
TABLE_MASTER = "%smasters" % TABLE_PREFIX
TABLE_FRAME = "%sframes" % TABLE_PREFIX

# host table names
TABLE_HOST = "%shosts" % TABLE_PREFIX
TABLE_HOST_SOFTWARE = "%s_software" % TABLE_HOST
TABLE_HOST_GROUP = "%s_group" % TABLE_HOST

# job tables
TABLE_JOB = "%sjobs" % TABLE_PREFIX
TABLE_JOB_SOFTWARE = "%s_software" % TABLE_JOB

# complete list of all table names
TABLE_NAMES = sorted([
    value for key, value in globals().copy().iteritems() \
    if key.startswith("TABLE_") and key != "TABLE_PREFIX"
])

# generate a list of everything this module exports
__all__ = [
    key for key in globals().copy().iterkeys() if not key.startswith("_")
]
