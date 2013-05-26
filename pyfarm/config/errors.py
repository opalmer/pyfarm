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


class PreferencesError(Exception):
    """base class for all preferences related errors"""


class PreferenceLoadError(PreferencesError):
    """raised when yaml can't load the preference file"""


class PreferencesNotFoundError(PreferencesError):
    """
    raised when we can't find any preference files by the requested name
    """


class SubKeyError(PreferencesError):
    """
    Raised when we failed to find a subkey for a given request.  Similar to
    :class:`KeyError` but spec to when requesting nested keys from a
    configuration
    """