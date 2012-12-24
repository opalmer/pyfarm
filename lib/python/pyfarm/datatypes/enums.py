# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
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

'''
named value mappings which do not change during execution
'''

import sys
from pyfarm.datatypes.functions import LoadEnum

Software = LoadEnum("Software")
SoftwareType = LoadEnum("SoftwareType")
State = LoadEnum("State")
EnvMergeMode = LoadEnum("EnvMergeMode")

def __os_get(self, item=None):
    if isinstance(item, (int, str, unicode)):
        return self.__mapped[item]

    platform = sys.platform
    if platform.startswith("linux"):
        platform = "linux"

    elif platform.startswith("win"):
        platform = "windows"

    elif platform not in OperatingSystem.MAPPINGS:
        return OperatingSystem.OTHER

    return self.__mapped[platform]
# end __os_get

OperatingSystem = LoadEnum(
    "OperatingSystem",
    methods={"get" : __os_get}
)
