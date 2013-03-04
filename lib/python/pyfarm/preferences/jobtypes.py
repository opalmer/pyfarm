# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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

import string
from os.path import sep

from pyfarm import dist
from pyfarm.datatypes.system import OSNAME
from pyfarm.preferences.base.baseclass import Preferences
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