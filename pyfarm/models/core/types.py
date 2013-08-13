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

try:
    from json import dumps, loads
except ImportError:
    from simplejson import dumps, loads

from sqlalchemy.types import TypeDecorator, CHAR, String
from sqlalchemy.dialects.postgresql import UUID as PGUuid

from pyfarm.flaskapp import db

JSON_NONE = dumps(None)
NoneType = type(None) # from stdlib types module


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
    allow_blank = False
    allow_empty = True

    def __init__(self, *args, **kwargs):
        super(JSONSerializable, self).__init__(*args, **kwargs)

        # make sure the subclass is doing something we expect
        if self.serialize_types is None:
            raise NotImplementedError("`serialize_types` is not defined")

    def process_bind_param(self, value, dialect):
        # TODO: simplify

        # value provided is already a string, try to
        # convert it into something we expect
        if isinstance(value, basestring):
            check = loads(value)

            if not isinstance(check, self.serialize_types):
                raise TypeError(
                    "failed to serialize %s to an expected data type" % value)

        # value provided is not something we expected to serialize
        elif not isinstance(value, self.serialize_types):
            raise TypeError(
                "%s is not an instance of %s" % self.serialize_types)

        if isinstance(value, basestring):
            if not self.allow_blank and not value:
                raise ValueError("value is blank")

        if not isinstance(value, basestring):
            value = dumps(value)

                # elif all([isinstance(value, self.serialize_types),
        #           not value, not self.allow_empty]):
        #     raise ValueError("object is empty")



        return value


class JSONList(JSONSerializable):
    """Special column type for storing lists as json"""
    serialize_types = (list, tuple, UserList)


class JSONDict(JSONSerializable):
    """Special column type for storing dictionary objects as json"""
    serialize_types = (dict, UserDict)


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
