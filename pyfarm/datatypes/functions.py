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
functions for use within the datatypes module
"""

# from pyfarm.pref.simple import EnumPreferences
try:
    from collections import namedtuple
except ImportError:
    from pyfarm.backports import namedtuple


def notimplemented(name, module='psutil'):
    msg = "this version of %s does not implement %s(), " % (module, name)
    msg += "please consider upgrading"
    raise NotImplementedError(msg)
# end notimplemented


def bytes_to_megabytes(value):
    return int(value / 1024 / 1024)
# end bytes_to_megabytes