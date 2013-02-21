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
#

"""
named value mappings which do not change during execution
"""

import imp
import sys
from pyfarm.datatypes.functions import LoadEnum

Software = LoadEnum("Software")
SoftwareType = LoadEnum("SoftwareType")
State = LoadEnum("State")
EnvMergeMode = LoadEnum("EnvMergeMode")

def __os_get(self, item=None):
    item = item.upper() if isinstance(item, (str, unicode)) else item
    if isinstance(item, (int, str, unicode)) and item in self.__mapped:
        return self.__mapped[item]

    elif isinstance(item, (int, str, unicode)) and item not in self.__mapped:
        raise ValueError("%s does not seem to be a valid mapping" % item)

    platform = sys.platform
    if platform.startswith("linux"):
        platform = "LINUX"

    elif platform.startswith("win"):
        platform = "WINDOWS"

    elif platform.startswith("darwin"):
        platform = "MAC"

    elif platform not in self.__mapped:
        return self.OTHER

    return self.__mapped[platform]
# end __os_get

OperatingSystem = LoadEnum(
    "OperatingSystem",
    methods={"get" : __os_get}
)

DependencyType = LoadEnum("DependencyType")
PythonExtensions = [ ext[0] for ext in imp.get_suffixes() ]
