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

class OperatingSystem:
    LINUX, WINDOWS, MAC, OTHER = range(4)
    MAPPINGS = {
        "win32" : WINDOWS,
        "cygwin" : WINDOWS,
        "darwin" : MAC,
        "linux" : LINUX,
        "mac" : MAC,
        WINDOWS : "win32",
        LINUX : "linux",
        MAC : "mac"
    }

    @staticmethod
    def get():
        '''returns the current operating system as an integer'''
        platform = sys.platform
        if platform.startswith("linux"):
            platform = "linux"

        value = OperatingSystem.MAPPINGS.get(platform)
        if value is None:
            return OperatingSystem.OTHER

        return value
    # end get

    @staticmethod
    def resolve(data):
        '''resolves the incoming string to a value'''
        return OperatingSystem.get(data)
    # end resolve
# end OperatingSystem

OS = OperatingSystem.get()
OSNAME = OperatingSystem.MAPPINGS.get(OS)
LIST_TYPES = (list, tuple, set)
