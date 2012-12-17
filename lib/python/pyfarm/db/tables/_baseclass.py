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

from sqlalchemy.types import Integer
from sqlalchemy.orm import object_session
from sqlalchemy import Column

from pyfarm.logger import Logger
from pyfarm.datatypes.enums import State

logger = Logger(__name__)

class PyFarmBase(object):
    '''
    base class which defines some base functions and attributes
    for all classes to inherit
    '''
    repr_attrs = ()
    repr_attrs_skip_none = False

    # base column definitions which all other classes inherit
    id = Column(Integer, primary_key=True, autoincrement=True)

    @property
    def session(self):
        return object_session(self)
    # end session

    def __getparentattr(self, name):
        '''retrieve a value from the right most class (including mixins)'''
        classes = []
        classes.extend(self.__class__.__bases__)
        classes.append(self.__class__)

        for base in reversed(classes):
            if hasattr(base, name):
                value = getattr(base, name)
                if value is not None and value:
                    return value
    # end __getparentattr

    def __repr__(self):
        values = []
        none = (None, repr(None), 'none')
        repr_attrs = self.__getparentattr('repr_attrs')
        repr_attrs_skip_none = self.__getparentattr('repr_attrs_skip_none')

        for attr in ( attr for attr in repr_attrs if hasattr(self, attr) ):
            original_value = getattr(self, attr)
            value = original_value

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
    # end __repr__
# end PyFarmBase
