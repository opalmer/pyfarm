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

from sqlalchemy.types import TypeDecorator, CHAR
from sqlalchemy.dialects.postgresql import UUID as PGUuid

from pyfarm.flaskapp import db


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
