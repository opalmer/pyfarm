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
Special column types used by PyFarm's models.
"""

from textwrap import dedent
from uuid import uuid4, UUID
from UserDict import UserDict
from UserList import UserList
from inspect import isclass
from importlib import import_module

from netaddr import IPAddress

try:
    from json import dumps, loads
except ImportError:
    from simplejson import dumps, loads

from sqlalchemy.types import TypeDecorator, CHAR, String
from sqlalchemy.dialects.postgresql import UUID as PGUuid

from pyfarm.flaskapp import db
from pyfarm.ext.jobtypes.core import Job

JSON_NONE = dumps(None)
NoneType = type(None)  # from stdlib types module


class GUID(TypeDecorator):
    """
    Platform-independent GUID type.

    Uses Postgresql's UUID type, otherwise uses
    CHAR(32), storing as stringified hex values.

    .. note::
        This code is copied from sqlalchemy's standard documentation
    """
    impl = CHAR

    def load_dialect_impl(self, dialect):
        if dialect.name == "postgresql":
            return dialect.type_descriptor(PGUuid())
        else:
            return dialect.type_descriptor(CHAR(32))

    def process_bind_param(self, value, dialect):
        if value is None:
            return value
        elif dialect.name == "postgresql":
            return str(value)
        else:
            if not isinstance(value, UUID):
                return "%.32x" % UUID(value)
            else:
                # hexstring
                return "%.32x" % value

    def process_result_value(self, value, dialect):
        if value is None:
            return value
        else:
            return UUID(value)


class JSONSerializable(TypeDecorator):
    """
    Base of all custom types which process json data
    to and from the database.

    :cvar serialize_types:
        the kinds of objects we expect to serialize to
        and from the database

    :cvar serialize_none:
        if True then return None instead of converting it to
        its json value

    :cvar allow_blank:
        if True, do not raise a :class:`ValueError` for empty data

    :cvar allow_empty:
        if True, do not raise :class:`ValueError` if the input data
        itself is empty
    """
    impl = String
    serialize_types = None
    serialize_none = False

    def __init__(self, *args, **kwargs):
        super(JSONSerializable, self).__init__(*args, **kwargs)

        # make sure the subclass is doing something we expect
        if self.serialize_types is None:
            raise NotImplementedError("`serialize_types` is not defined")

    def dumps(self, value):
        """
        Performs the process of dumping `value` to json.  For classes
        such as :class:`UserDict` or :class:`UserList` this will dump the
        underlying data instead of the object itself.
        """
        if isinstance(value, (UserDict, UserList)):
            value = value.data

        return dumps(value)

    def process_bind_param(self, value, dialect):
        """Converts the value being assigned into a json blob"""
        if value is None:
            return self.dumps(value) if self.serialize_none else value

        elif isinstance(value, self.serialize_types):
            return self.dumps(value)

        else:
            args = (value, self.__class__.__name__)
            raise ValueError("unexpected input %s for `%s`" % args)

    def process_result_value(self, value, dialect):
        """Converts data from the database into a Python object"""
        return value if value is None else loads(value)


class JSONList(JSONSerializable):
    """Column type for storing list objects as json"""
    serialize_types = (list, tuple, UserList)


class JSONDict(JSONSerializable):
    """Column type for storing dictionary objects as json"""
    serialize_types = (dict, UserDict)


class IPv4Address(TypeDecorator):
    """
    Column type which can store and retrieve IPv4 addresses in a more
    efficient manner
    """
    MAX_INT = 4294967295

    def checkInteger(self, value):
        if value < 0 or value > self.MAX_INT:
            args = (value, self.__class__.__name__)
            raise ValueError("invalid integer '%s' for %s" % args)

        return value

    def process_bind_param(self, value, dialect):
        if isinstance(value, int):
            return self.checkInteger(value)

        elif isinstance(value, basestring):
            return self.checkInteger(int(IPAddress(value)))

        elif isinstance(value, IPAddress):
            return self.checkInteger(int(value))

        else:
            raise ValueError("unexpected type %s for value" % type(value))

    def process_result_value(self, value, dialect):
        value = IPAddress(value)
        self.checkInteger(int(value))
        return value


class JobType(TypeDecorator):
    """
    Column type which loads and stores job types.
    """
    MODULE_ROOT = "pyfarm.ext.jobtypes.%s"

    def process_bind_param(self, value, dialect):
        if isinstance(value, Job) or isclass(value):
            return value.__name__
        elif isinstance(value, basestring):
            return value
        else:
            args = (type(value), self.__class__.__name__)
            raise TypeError("unsupported type %s for %s" % args)

    def process_result_value(self, value, dialect):
        if value is None:
            raise ValueError("value provided for `jobtype` cannot be None")

        module_name = value.lower()
        module_path = self.MODULE_ROOT % module_name

        # attempt to import the module for the job type
        try:
            module = import_module(module_path)
        except ImportError:
            args = (module_name, module_path)
            raise ImportError(
                "failed to find a job type to import for %s at %s" % args)

        # try to get the class attribute and return it
        try:
            return getattr(module, value)
        except AttributeError:
            raise AttributeError(
                "job type %s does exist on %s" % (value, module))

def IDColumn():
    """
    Produces a column used for `id` on each table.  Typically this is done
    using a class in :mod:`pyfarm.models.mixins` however because of the ORM
    and the table relationships it's cleaner to have a function produce
    the column.
    """
    return db.Column(IDType, primary_key=True, unique=True, default=IDDefault,
                     doc=dedent("""
                     Provides an id for the current row.  This value should
                     never be directly relied upon and it's intended for use
                     by relationships."""))


# the universal mapping which can be used, even if the underlying
# type changes in the future
IDType = GUID
IDDefault = uuid4
