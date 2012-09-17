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

import os
import sys
import psutil
import getpass

from pyfarm.datatypes.functions import bytes_to_megabytes

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

# getlogin is the preferred way to get the current
# login because it does not directly look to the
# environment for the username
try:
    import pwd
    USER = pwd.getpwuid(os.getuid())[0]

except ImportError:
    USER = getpass.getuser()

# setup total swap and current swap
if hasattr(psutil, 'swap_memory'):
    TOTAL_SWAP = int(psutil.swap_memory().total / 1024 / 1024)
    swap = lambda: bytes_to_megabytes(psutil.swap_memory().free)
elif hasattr(psutil, 'virtmem_usage'):
    TOTAL_SWAP = int(psutil.virtmem_usage().total / 1024 / 1024)
    swap = lambda : bytes_to_megabytes(psutil.virtmem_usage().free)
else:
    TOTAL_SWAP = int(psutil.total_virtmem() / 1024 / 1024)
    swap = lambda: TOTAL_SWAP - bytes_to_megabytes(psutil.used_virtmem())

# setup current ram
if hasattr(psutil, 'virtual_memory'):
    ram = lambda: bytes_to_megabytes(psutil.virtual_memory().free)

elif hasattr(psutil, 'phymem_usage'):
    ram = lambda: bytes_to_megabytes(psutil.phymem_usage().free)

else:
    ram = lambda: TOTAL_RAM - bytes_to_megabytes(psutil.used_phymem())

# docstrings for swap and ram
swap.__doc__ = "returns the current swap available"
ram.__doc__ = "returns the current ram available"
