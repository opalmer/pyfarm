#!/usr/bin/env python
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
