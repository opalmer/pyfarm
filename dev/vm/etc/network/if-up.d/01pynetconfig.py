#!/usr/bin/env python
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

"""
Iterates over all network interfaces and retrieves an ip address for
those we can bring up.
"""

import os
import netifaces

for name in netifaces.interfaces():
    # skip loopback interfaces
    if name.startswith("lo"):
        continue

    # attempt to retrieve an ip address for interface(s)
    # we can bring up
    if not os.system("ifconfig %s up" % name):
        os.system("dhclient")
