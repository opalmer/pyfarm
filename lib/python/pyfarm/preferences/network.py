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

import re
from pyfarm.preferences.base.baseclass import Preferences
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


