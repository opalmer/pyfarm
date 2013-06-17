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

try:
    from pyfarm.admin.tables.job import (
        JobModelView, JobTagsModelView, JobSoftwareModelView
    )
    from pyfarm.admin.tables.agent import (
        AgentModelView, AgentTagsModelView, AgentSoftwareModelView
    )
    from pyfarm.admin.tables.task import TaskModelView
except ImportError:
    import sys

    if sys.version_info[0:2] <= (2, 5):
        from pyfarm.warning import CompatibilityWarning
        from warnings import warn
        warn("admin modules require Python 2.6 or higher", CompatibilityWarning)
    else:
        raise
