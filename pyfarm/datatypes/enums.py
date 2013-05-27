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
from pyfarm.config.enum import (
    Software as _Softare, SoftwareType as _SoftwareType,
    State as _State, EnvMergeMode as _EnvMergeMode,
    DependencyType as _DependencyType, OperatingSystem as _OperatingSystem
)

Software = _Softare()
SoftwareType =_SoftwareType()
State = _State()
EnvMergeMode = _EnvMergeMode()
OperatingSystem = _OperatingSystem()
DependencyType = _DependencyType()
PythonExtensions = [ext[0] for ext in imp.get_suffixes()]
