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

import string
from os.path import sep

from pyfarm import dist
from pyfarm.datatypes.system import OSNAME
from pyfarm.pref.core.baseclass import Preferences
from pyfarm.utility import expandPath


class JobTypePreferences(Preferences):
    """jobtype preferences"""
    def __init__(self):
        super(JobTypePreferences, self).__init__(filename="jobtypes")
    # end __init__

    # TODO: add function for file extension generation

    def _search_paths(self, data):
        paths = []

        for entry in data:
            template = string.Template(entry.replace("/", sep))
            expanded = expandPath(template.safe_substitute({
                "pyfarm" : dist.location,
                "root" : self.get(
                    "filesystem.roots.%s" % OSNAME.lower(), filename=None
                )
            }))

            if expanded not in paths:
                paths.append(expanded)

        return paths
    # ene _search_paths

    def get(self, key, **kwargs):
        """
        overrides :meth:`Preferences.get` to handle special cases for
        the database urls
        """
        data = super(JobTypePreferences, self).get(key, **kwargs)
        if key.endswith("search-paths"):
            return self._search_paths(data)
    # end get
# end JobTypePreferences


j = JobTypePreferences()
print j.get('search-paths')