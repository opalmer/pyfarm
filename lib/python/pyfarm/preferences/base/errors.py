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

"""errors that preferences can raise"""


class PreferenceLoadError(OSError):
    """raised whenever we have trouble loading a preference file"""
    pass
# end PreferenceLoadError


class EmptyPreferenceError(ValueError):
    """
    raised when a preference file we attempted to load does not contain data.
    """
    pass
# end EmptyPreferenceError
