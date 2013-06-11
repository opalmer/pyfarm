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

from pyfarm.ext.config.core.loader import Loader

<<<<<<< HEAD
DBDATA = Loader("dbdata.yml")

TABLE_PREFIX = DBDATA.get("tables.prefix")
TABLE_AGENT = "%sagent" % TABLE_PREFIX
TABLE_AGENT_TAGS = "%s_tags" % TABLE_AGENT
TABLE_AGENT_SOFTWARE = "%s_software" % TABLE_AGENT
TABLE_JOB = "%sjob" % TABLE_PREFIX
TABLE_JOB_TAGS = "%s_tags" % TABLE_JOB
TABLE_JOB_SOFTWARE = "%s_software" % TABLE_JOB
TABLE_TASK = "%stask" % TABLE_PREFIX
=======
# regex constants
REGEX_IPV4 = re.compile("^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$")
REGEX_HOSTNAME = re.compile("^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$")

# table field constants
MAX_HOSTNAME_LENGTH = 255
MAX_SOFTWARE_LENGTH = 128
MAX_GROUP_LENGTH = 128
MAX_IPV4_LENGTH = 15
MAX_USERNAME_LENGTH = 255

# agent constants
MIN_RAM_MB = 1
MAX_RAM_MB = 1.7592186e13
MIN_AGENT_PORT = 1025
MAX_AGENT_PORT = 65500

# database constants
TABLE_PREFIX = "pyfarm_"
TABLE_AGENT = "%sagent" % TABLE_PREFIX
TABLE_TASK = "%stask" % TABLE_PREFIX
TABLE_SOFTWARE = "%ssoftware" % TABLE_PREFIX
>>>>>>> 8c86fa216a3cd88aa9fe3594ae745996725f5bfe
