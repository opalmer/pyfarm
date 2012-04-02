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

from PyQt4.QtCore import QString
from sqlalchemy.types import String

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

class Enum(object):
    '''
    Simple class which converts arguments to class attributes with
    an assigned number.

    :param args:
        string arguments which will create instance
        attributes

    :param integer start:
        keyword argument which controls the start of the sequence

    :param string name:
        the name to provide when str(<enum instance>) is called

    :exception TypeError:
        raised if a value in the incoming arguments is not a string

    :exception KeyError:
        raised if the value provided to get() or __getitem__ is neither
        a string provided as an argument to __init__ or an integer
        which was mapped to an argument
    '''
    def __init__(self, *args, **kwargs):
        self._start = kwargs.get('start', 0)
        self._end = self._start+len(args)
        self.__mappings = {}
        self.__range = xrange(*(self._start, self._end, 1))

        # establish name to use when __repr__ is called
        name = kwargs.get('name') or self.__class__.__name__
        self.__name = name.upper()

        index = 0
        for arg in args:
            if not isinstance(arg, str):
                raise TypeError("%s is not a string" % str(arg))

            index_value = self.__range[index]

            # provide both a string mapping and an integer
            # mapping for use with __getitem__ and get()
            self.__mappings[index_value] = arg
            self.__mappings[arg] = index_value

            # set the attribute on the class
            setattr(self, arg, index_value)

            index += 1

    # external methods
    def __repr__(self): return self.__name
    def __getitem__(self, item): return self.__mappings[item]
    def get(self, item): return self.__getitem__(item)
# end Enum


Software = Enum("MAYA", "HOUDINI", "VRAY", "NUKE", "BLENDER")
State = Enum(
    "PAUSED", "BLOCKED", "QUEUED", "ASSIGN",
    "RUNNING", "FINISHED", "FAILED"
)

# python datatypes for type comparison
LIST_TYPES = (list, tuple, set)
BOOLEAN_TYPES = (True, False)
STRING_TYPES = (str, unicode, QString, String)

OS = OperatingSystem.get()
OSNAME = OperatingSystem.MAPPINGS.get(OS)
ACTIVE_JOB_STATES = (State.QUEUED, State.RUNNING)