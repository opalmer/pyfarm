# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
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

import sys
import psutil

__all__ = ['OperatingSystem', 'OS', 'OSNAME', 'CPU_COUNT', 'TOTAL_RAM', 'TOTAL_SWAP']

class OperatingSystem:
    LINUX, WINDOWS, MAC, OTHER = range(4)
    MAPPINGS = {
        "windows" : WINDOWS,
        "cygwin" : WINDOWS,
        "darwin" : MAC,
        "linux" : LINUX,
        "mac" : MAC,
        WINDOWS : "windows",
        LINUX : "linux",
        MAC : "mac"
    }

    @staticmethod
    def get(value=None):
        '''
        returns the current operating system as an integer or the assoicated
        entry for the given value

        :exceptoion KeyError:
            raised if value is not None and is not in OperatingSystem.MAPPINGS
        '''
        if isinstance(value, (int, str, unicode)):
            return OperatingSystem.MAPPINGS[value]

        platform = sys.platform
        if platform.startswith("linux"):
            platform = "linux"

        elif platform.startswith("win"):
            platform = "windows"

        elif platform not in OperatingSystem.MAPPINGS:
            return OperatingSystem.OTHER

        return OperatingSystem.MAPPINGS[platform]
        # end get
# end OperatingSystem

OS = OperatingSystem.get()
OSNAME = OperatingSystem.MAPPINGS.get(OS)
CPU_COUNT = psutil.NUM_CPUS
TOTAL_RAM = int(psutil.TOTAL_PHYMEM / 1024 / 1024)

if hasattr(psutil, 'swap_memory'):
    TOTAL_SWAP = int(psutil.swap_memory().total / 1024 / 1024)
elif hasattr(psutil, 'virtmem_usage'):
    TOTAL_SWAP = int(psutil.virtmem_usage().total / 1024 / 1024)
else:
    TOTAL_SWAP = int(psutil.total_virtmem() / 1024 / 1024)
