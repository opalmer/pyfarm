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

from pyfarm import datatypes

from sqlalchemy import Table
from twisted.python import log

def typecheck(table, data):
    '''
    checks the given data again the provided table

    :exception TypeError:
        raised if one or more of the keys in data does not match
        the exepcted type for the given column in the table

    :exception KeyError:
        raised if one or more of the keys in data do not exist
        as columns
    '''
    log.msg("testing types of %s aginst %s" % (data, table.name))

    # ensure the type being provided by table is correct
    if not isinstance(table, Table):
        raise TypeError("invalid type for table, expected sqlalchemy.Table")

    # ensure we are getting the correct type for data
    if not isinstance(data, dict):
        raise TypeError("expected dictionary for data")

    # check all keys in data to ensure we are not
    # trying to add or edit columns which do not
    # exist
    expected_key = table.c.keys()
    for key in data.iterkeys():
        if key not in expected_key:
            raise KeyError(
                "%s is not part of the expected keys %s" % expected_key
            )

    # iterate over all keys and value in data and checks
    # their types
    for key, value in data.iteritems():
        column = getattr(table.c, key)

        # ensure the type we are checking is part
        # of the types we are checking for
        column_type = column.type.__class__
        if column_type not in datatypes.SQL_TYPES:
            msg = "cannot type for %s, type not defined " % column_type
            msg += "in datatypes.SQL_TYPES"
            raise KeyError(msg)

        if not isinstance(value, datatypes.SQL_TYPES[column_type]):
            args = (key, datatypes.SQL_TYPES[column_type])
            raise TypeError("invalid type for %s, expected %s" % args)
# end typecheck
