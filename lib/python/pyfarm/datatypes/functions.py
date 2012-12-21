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

'''
functions for use within the datatypes module
'''

import os

from pyfarm.fileio import yml
from pyfarm import PYFARM_ETC
from pyfarm.datatypes._types import namedtuple

ENUM_DATA = yml.load(os.path.join(PYFARM_ETC, "enums.yml"))

def notimplemented(name, module='psutil'):
    msg = "this version of %s does not implement %s(), " % (module, name)
    msg += "please consider upgrading"
    raise NotImplementedError(msg)
# end notimplemented

def bytes_to_megabytes(value):
    return int(value / 1024 / 1024)
# end bytes_to_megabytes

def LoadEnum(name, additional_methods=None, classonly=False):
    '''
    return an enum class with the given name

    :param dict additional_methods:
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
    mapped = dict(
        zip(data.iterkeys(), data.itervalues()) +
        zip(data.itervalues(), data.iterkeys())
    )

    # construct methods which will build the class
    methods = {
        "__contains__" : lambda self, item: item in mapped,
        "__dir__" : lambda self: data.keys(),
        "get" : lambda self, name: mapped[name],
        "keys" : lambda self: data.keys(),
        "values" : lambda self: data.values()
    }

    if additional_methods is not None and isinstance(additional_methods, dict):
        methods.update(additional_methods)

    elif additional_methods is not None and not isinstance(additional_methods, dict):
        raise TypeError("additional_methods should be a dictionary")


    # construct the new class type, bind the methods, and return
    # an instance of the new class
    newclass = type(name, (named_tuple,), methods)
    return newclass if classonly else newclass(*data.itervalues())
# end LoadEnum
