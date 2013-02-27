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

"""
module for common preference classes
"""

from pyfarm.preferences.base.baseclass import Preferences

class NamedPreferences(Preferences):
    """
    simple class which will use either the current class name or
    :attr:`load_file` to inform the preferences class of which file name to
    use
    """
    load_file = None

    def __init__(self):
        if self.load_file is None:
            filename = self.__class__.__name__.lower()
        else:
            filename = self.load_file
        super(NamedPreferences, self).__init__(filename=filename)
    # end __init__
# end NamedPreferences


class LoggingPreferences(NamedPreferences):
    """logging preferences"""
    load_file = "logging"
# end LoggingPreferences


class JobSystemPreferences(NamedPreferences):
    """jobsystem preferences"""
    load_file = "jobsystem"
# end JobSystemPreferences


class FilesystemPreferences(NamedPreferences):
    """filesystem preferences"""
    load_file = "filesystem"
# end FilesystemPreferences
