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
Stores basic configuration data related to tables and models.

:var DBCFG: Instanced
    :class:`Loader` class which loads dbdata.yml

:var TABLE_PREFIX:
    Prefix for all tables, defaults to dbdata.yml:tables.prefix

:var TABLE_AGENT:
    Stores the name of the table for agents

:var TABLE_AGENT_TAGS:
    Stores the name of the table for agent tags

:var TABLE_AGENT_SOFTWARE:
    Stores the name of the table for agent software

:var TABLE_JOB:
    Stores the name of the table for jobs

:var TABLE_JOB_TAGS:
    Stores the name of the table for job tags

:var TABLE_JOB_SOFTWARE:
    Stores the name of the table for job software

:var TABLE_TASK:
    Stores the name of the table for job tasks
"""

from pyfarm.ext.config.core.loader import Loader

DBCFG = Loader("dbdata.yml")

TABLE_PREFIX = DBCFG.get("tables.prefix")
TABLE_AGENT = "%sagent" % TABLE_PREFIX
TABLE_AGENT_TAGS = "%s_tags" % TABLE_AGENT
TABLE_AGENT_SOFTWARE = "%s_software" % TABLE_AGENT
TABLE_JOB = "%sjob" % TABLE_PREFIX
TABLE_JOB_TAGS = "%s_tags" % TABLE_JOB
TABLE_JOB_SOFTWARE = "%s_software" % TABLE_JOB
TABLE_TASK = "%stask" % TABLE_PREFIX