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

import os
import sys

msg = "admin modules require Python 2.6 or higher"

if sys.version_info[0:2] <= (2, 5) and "BUILDBOT_UUID" in os.environ:
    from nose.plugins.skip import SkipTest
    raise SkipTest(msg)
else:
    from pyfarm.warning import CompatibilityWarning
    from warnings import warn
    warn(msg, CompatibilityWarning)
    raise