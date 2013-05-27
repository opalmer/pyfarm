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

import sys
from warnings import warn
from textwrap import dedent

try:
    from collections import namedtuple
except ImportError:
    from pyfarm.backports import namedtuple

from pyfarm.config.core import Loader


class EnumBuilder(type):
    """
    Metaclass which is used by enum objects to build
    their class attributes based upon entries in `enums.yml`
    """
    def __new__(mcs, classname, parents, attributes):
        # loads the static values from enums.yml
        # and turns them into attributes
        yml_data = Loader("enums.yml")
        data = yml_data[classname]
        attributes.update(data)

        # create a mapping with all values then assign them
        # as attributes
        mapped = dict(
            zip(data.iterkeys(), data.itervalues()) +
            zip(data.itervalues(), data.iterkeys())
        )
        attributes.setdefault("_data", data)
        attributes.setdefault("_mapped", mapped)

        # generate the doc string
        doc = dedent("""
        Enum object for %s.  See :class:`Enum` for the method
        documentation and
        :download:`enums.yml <../../../pyfarm/config/etc/enums.yml>` for the
        underlying data
        """ % classname)

        attributes.setdefault("__doc__", doc)
        return type.__new__(mcs, classname, parents, attributes)


class Enum(object):
    """Base class of enums objects used to storing common methods"""
    def __contains__(self, item):
        """
        checks if item is a member of either the mapped data or the data
        from `enums.yml`
        """
        return item in self._mapped

    @classmethod
    def get(cls, key, failobj=None):
        """retrieve a value either by name (ex. 'MAYA') or by value (ex. 6)"""
        return cls._mapped.get(key, failobj)

    def keys(self):
        """returns all keys provided by `enums.yml`"""
        return self._data.keys()

    def values(self):
        """returns all values provided by `enums.yml`"""
        return self._data.values()


class Software(Enum):
    __metaclass__ = EnumBuilder


class SoftwareType(Enum):
    __metaclass__ = EnumBuilder


class State(Enum):
    __metaclass__ = EnumBuilder


class EnvMergeMode(Enum):
    __metaclass__ = EnumBuilder


class DependencyType(Enum):
    __metaclass__ = EnumBuilder


class OperatingSystem(Enum):
    __metaclass__ = EnumBuilder

    def get(self, key=None, failobj=None):
        """
        Override of :meth:`Enum.get` which will return the current operating
        system if not provided any input.
        """
        if key is not None:
            return super(OperatingSystem, self).get(key, failobj=failobj)
        elif sys.platform.startswith("linux"):
            return self.LINUX
        elif sys.platform.startswith("win"):
            return self.WINDOWS
        elif sys.platform.startswith("darwin"):
            return self.MAC
        else:
            warn(
                "unknown operating system: %s" % sys.platform,
                RuntimeWarning)
            return self.OTHER
