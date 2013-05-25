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

import re
from pyfarm.pref.core.baseclass import Preferences
from pyfarm.net.functions import openport


class NetworkPreferences(Preferences):
    """retrieves network preferences"""
    # stores a dictionary of ports we have
    GENERATED_HOST_PORTS = {}
    RE_PORT_REQUEST = re.compile(".*ports[.](.+)")

    def __init__(self):
        super(NetworkPreferences, self).__init__(filename="network")
    # end __init__

    def get(self, key, **kwargs):
        """
        overrides :meth:`Preferences.get` to handle special cases for
        host ports and other possible edge cases
        """
        try:
            value = super(NetworkPreferences, self).get(key, **kwargs)
            return value

        except KeyError:
            match = self.RE_PORT_REQUEST.match(key)

            # if we get a match from the regular expression then
            # we are requesting a port key in which case we should
            # generate an unused port if we have not already
            if match is not None:
                name = match.group(1)
                if name not in self.GENERATED_HOST_PORTS:
                    self.GENERATED_HOST_PORTS[name] = openport()
                return self.GENERATED_HOST_PORTS[name]
    # end get
# end NetworkPreferences


