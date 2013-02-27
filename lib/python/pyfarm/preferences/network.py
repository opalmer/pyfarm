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

from pyfarm.preferences.base.baseclass import Preferences
from pyfarm.net.functions import openport

class NetworkPreferences(Preferences):
    """retrieves network preferences"""
    # stores a dictionary of ports we have
    GENERATED_HOST_PORTS = {}
    def __init__(self):
        super(NetworkPreferences, self).__init__(filename="network")
    # end __init__

    def get(self, key, **kwargs):
        """
        overrides :meth:`Preferences.get` to handle special cases for
        host ports and other possible edge cases
        """
        if "ports." in key:
            end = key.split(".")[-1]
            if end not in self.GENERATED_HOST_PORTS and end != "ports":
                self.GENERATED_HOST_PORTS[end] = openport()
            return self.GENERATED_HOST_PORTS[end]

        return super(NetworkPreferences, self).get(key, **kwargs)
    # end get
# end NetworkPreferences


