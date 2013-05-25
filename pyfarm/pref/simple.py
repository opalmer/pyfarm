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
module for common preference classes
"""

from pyfarm.pref.core.baseclass import Preferences


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


class EnumPreferences(NamedPreferences):
    """enum preferences"""
    load_file = "enums"
# end EnumPreferences