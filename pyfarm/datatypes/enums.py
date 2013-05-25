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
