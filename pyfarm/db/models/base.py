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

from itertools import ifilter
from sqlalchemy import Column, Integer
from sqlalchemy.orm import object_session
from pyfarm.datatypes.enums import State


class TableBase(object):
    """
    Base class which defines some base functions and attributes
    for all tables to inherit.

    :type repr_attrs: list or tuple
    :cvar repr_attrs:
        if provided only produce these attributes in `__repr__`

    :cvar repr_attrs_skip_none:
    """
    repr_attrs = ()
    repr_attrs_skip_none = False

    # inherited by all other tables
    id = Column(Integer, primary_key=True, autoincrement=True)

    @property
    def session(self):
        return object_session(self)

    def __getparentattr(self, name):
        """retrieve a value from the right most class (including mixins)"""
        classes = list(self.__class__.__bases__)
        classes.append(self.__class__)

        for base in reversed(classes):
            if hasattr(base, name):
                value = getattr(base, name)
                if value is not None and value:
                    return value

    def __repr__(self):
        values = []
        none = (None, repr(None), 'none')
        repr_attrs = self.__getparentattr('repr_attrs')
        repr_attrs_skip_none = self.__getparentattr('repr_attrs_skip_none')
        _hasattr = lambda attribute: hasattr(self, attribute)

        for attr in ifilter(_hasattr, repr_attrs):
            original_value = getattr(self, attr)

            if attr == 'state' and original_value is not None:
                value = State.get(original_value)

            elif isinstance(original_value, unicode):
                value = "'%s'" % original_value

            else:
                value = repr(original_value)

            if repr_attrs_skip_none and value in none:
                continue

            values.append("%s=%s" % (attr, value))

        return "%s(%s)" % (self.__class__.__name__, ", ".join(values))
