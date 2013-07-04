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
Returns information about memory including swap usage, system memory usage,
and general capacity information.
"""

import psutil
from pyfarm.utility import convert


class MemoryInfo(object):
    """
    .. note::
        This class has already been instanced onto `pyfarm.system.memory`

    Namespace class which returns information about both physical and
    virtual memory on the system.  Unless otherwise noted all units are in
    megabytes.

    This class is a wrapper around methods present on :mod:`psutil` and is
    normally accessed using the instance found on `memory`:

    :attr TOTAL_RAM:
        Total physical memory (ram) installed on the system

    :attr TOTAL_SWAP:
        Total virtual memory (swap) installed on the system
    """
    TOTAL_RAM = convert.bytetomb(psutil.TOTAL_PHYMEM)
    TOTAL_SWAP = convert.bytetomb(psutil.swap_memory().total)

    def swapUsed(self):
        """Amount of swap currently in use"""
        return convert.bytetomb(psutil.swap_memory().used)

    def swapFree(self):
        """Amount of swap currently free"""
        return convert.bytetomb(psutil.swap_memory().free)

    def ramUsed(self):
        """Amount of swap currently free"""
        return convert.bytetomb(psutil.virtual_memory().used)

    def ramFree(self):
        """Amount of ram currently free"""
        return convert.bytetomb(psutil.virtual_memory().available)


