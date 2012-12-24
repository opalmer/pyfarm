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

'''
functions for use within the datatypes module
'''

import os

from pyfarm.fileio import yml
from pyfarm import PYFARM_ETC
from pyfarm.datatypes._types import namedtuple
from pyfarm.datatypes.objects import ReadOnlyDict

ENUM_DATA = yml.load(os.path.join(PYFARM_ETC, "enums.yml"))

def notimplemented(name, module='psutil'):
    msg = "this version of %s does not implement %s(), " % (module, name)
    msg += "please consider upgrading"
    raise NotImplementedError(msg)
# end notimplemented

def bytes_to_megabytes(value):
    return int(value / 1024 / 1024)
# end bytes_to_megabytes

def LoadEnum(name, methods=None, classonly=False):
    '''
    return an enum class with the given name

    :param dict methods:
        dictionary of additonal methods to add to the class

    :param boolean classonly:
        if True then return the class itself and not an instance
    '''
    try:
        data = ENUM_DATA[name]

    except KeyError:
        raise KeyError("enum %s does not have any configuration data" % name)

    named_tuple = namedtuple(name, data.keys())

    # create a mapping: {"FOO" : 1} -> {"FOO" : 1, 1 : "FOO"}
    mapped = ReadOnlyDict(
        zip(data.iterkeys(), data.itervalues()) +
        zip(data.itervalues(), data.iterkeys())
    )

    methods = {} if methods is None else methods
    if not isinstance(methods, dict):
        raise TypeError("methods must be a dictionary")

    # construct methods which will build the class
    standard_methods = {
        "__contains__" : lambda self, item: item in self.__mapped,
        "get" : lambda self, name: self.__mapped[name],
        "keys" : lambda self: self.__mapped.keys(),
        "values" : lambda self: self.__mapped.values()
    }

    # add the standard methods but only if the
    # method does not already exist in the provided methods
    for key, value in standard_methods.iteritems():
        methods.setdefault(key, value)

    # construct the new class type, bind the methods, and return
    # an instance of the new class
    newclass = type(name, (named_tuple,), methods)
    newclass.__mapped = mapped

    return newclass if classonly else newclass(*data.itervalues())
# end LoadEnum
