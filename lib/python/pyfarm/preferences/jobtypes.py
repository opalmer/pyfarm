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

from pyfarm.preferences.base.baseclass import Preferences


class JobTypePreferences(Preferences):
    """jobtype preferences"""
    def __init__(self):
        super(JobTypePreferences, self).__init__(filename="jobtypes")
    # TODO: add function for file extension generation
    # TODO: add function for $PATH generation

    def get(self, key, **kwargs):
        """
        overrides :meth:`Preferences.get` to handle special cases for
        the database urls
        """
        return super(JobTypePreferences, self).get(key, **kwargs)
    # end get
# end JobTypePreferences
