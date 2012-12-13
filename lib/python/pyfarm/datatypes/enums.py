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
#

'''
named value mappings which do not change during execution
'''

import datetime

try:
    from collections import OrderedDict

except ImportError:
    from ordereddict import OrderedDict

from sqlalchemy import types as sqltypes

class Enum(object):
    '''
    Simple class which converts arguments to class attributes with
    an assigned number.

    :param args:
        string arguments which will create instance
        attributes

    :param string name:
        the name to provide when str(<enum instance>) is called

    :exception TypeError:
        raised if a value in the incoming arguments is not a string

    :exception KeyError:
        raised if the value provided to get() or __getitem__ is neither
        a string provided as an argument to __init__ or an integer
        which was mapped to an argument
    '''
    def __init__(self, *args):
        self.__mappings = OrderedDict()
        self.__args = list(args)
        self.__repr = "%s(%s)" % (self.__class__.__name__, ", ".join(self.__args))
        self.__keys = []
        self.__values = []

        for index, arg in enumerate(self.__args):
            if not isinstance(arg, (str, unicode)):
                raise TypeError("%s is not a string" % str(arg))

            # internal mapping of the input values to be
            # used for keys() and values()
            self.__keys.append(arg)
            self.__values.append(index)

            # provide both a string mapping and an integer
            # mapping for use with __getitem__ and get()
            self.__mappings[index] = arg
            self.__mappings[arg] = index

            # set the attribute on the class
            setattr(self, arg, index)

        # recast as tuples so iteration is faster and
        # the data is read only
        self.__keys = tuple(self.__keys)
        self.__values = tuple(self.__values)
    # end __init__

    def __repr__(self): return self.__repr
    def __getitem__(self, item): return self.__mappings[item]
    def __contains__(self, item): self.__getitem__(item)
    def get(self, item): return self.__mappings[item]
    def keys(self): return self.__keys
    def values(self): return self.__values
# end Enum

Software = Enum("MAYA", "HOUDINI", "VRAY", "NUKE", "BLENDER")
State = Enum(
    "PAUSED", "BLOCKED", "QUEUED", "ASSIGN",
    "RUNNING", "DONE", "FAILED"
)
SoftwareType = Enum(
    "INCLUDE", "EXCLUDE"
)
EnvMergeMode = Enum(
    # UPDATE - env1.update(env2)
    # REPLACE - env1 = env2
    # FILL - for key, value in env2.iteritems(): env1.setdefault(key, value)
    "UPDATE", "REPLACE", "FILL"
)

# python datatypes for type comparison
ACTIVE_HOSTS_FRAME_STATES = (State.RUNNING, State.ASSIGN)
ACTIVE_JOB_STATES = (State.QUEUED, State.RUNNING)
ACTIVE_FRAME_STATES = (State.QUEUED, State.FAILED)
ACTIVE_DEPENDENCY_STATES = (State.RUNNING, State.QUEUED, State.FAILED)

# defaults when creating host
DEFAULT_GROUPS = ['*']
DEFAULT_SOFTWARE = ['*']
DEFAULT_JOBTYPES = ['*']
SQL_TYPES = {
    sqltypes.Integer : (int, ),
    sqltypes.String : (str, unicode),
    sqltypes.Text : (str, unicode),
    sqltypes.Float : (float, int),
    sqltypes.PickleType : (int, float, str, unicode, list, tuple, set, dict),
    sqltypes.Boolean : (bool, ),
    sqltypes.DateTime : (datetime.datetime, )
}
