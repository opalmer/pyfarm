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
functions for use within the datatypes module
"""

from pyfarm.preferences.simple import EnumPreferences
from pyfarm.datatypes.backports import namedtuple
from pyfarm.datatypes.objects import ReadOnlyDict

# TODO: replace with new preference loader
enumprefs = EnumPreferences()

def notimplemented(name, module='psutil'):
    msg = "this version of %s does not implement %s(), " % (module, name)
    msg += "please consider upgrading"
    raise NotImplementedError(msg)
# end notimplemented

def bytes_to_megabytes(value):
    return int(value / 1024 / 1024)
# end bytes_to_megabytes

def LoadEnum(name, methods=None, classonly=False):
    """
    return an enum class with the given name

    :param dict methods:
        dictionary of additonal methods to add to the class

    :param boolean classonly:
        if True then return the class itself and not an instance
    """
    try:
        data = enumprefs.get(name)

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
