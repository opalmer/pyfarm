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
import types

class OperatingSystem:
    MAPPINGS = {
        "win32" : OperatingSystem.WINDOWS,
        "cygwin" : OperatingSystem.WINDOWS,
        "darwin" : OperatingSystem.MAC,
        "mac" : OperatingSystem.MAC
    }
    LINUX, WINDOWS, MAC, OTHER = range(4)

    @staticmethod
    def get():
        '''returns the current operating system as an integer'''
        value = OperatingSystem.MAPPINGS.get(sys.platform)
        if isinstance(value, types.NoneType):
            return OperatingSystem.OTHER

        return value
    # end get

    @staticmethod
    def resolve(data):
        '''resolves the incoming string to a value'''
        return OperatingSystem.get(data)
    # end resolve
# end OperatingSystem


# TODO: add method for list resolution
class Software:
    pass
# end Software


# TODO: add method for list resolution
class JobType:
# end JobType
