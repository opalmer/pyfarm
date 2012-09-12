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

from __future__ import with_statement

from pyfarm.db import transaction
from pyfarm import errors

from twisted.python import log

def modify(table, match_column_name, match_data,
           exception_notfound=errors.NotFoundError,
           exception_duplicate=errors.DuplicateEntry,
           **columns):
    '''
    mods the requested table for any entries matching match_data
    in match_column_name
    '''
    if not columns:
        raise ValueError("no columns provided to update")

    args = (match_column_name, match_data, table)
    log.msg("preparing to modify columns %s matching %s in %s" % args)

    with transaction.Transaction(table) as trans:
        match_column = getattr(table.c, match_column_name)
        filter = trans.query.filter(match_column == match_data)
        count = filter.count()

        if not count:
            raise exception_notfound(
                column_name=match_column_name,
                match_data=match_data,
                table=table
            )

        elif count > 1:
            raise exception_duplicate(
                column_name=match_column_name,
                match_data=match_data,
                table=table
            )

        ids = []
        for entry in filter:
            modified = False
            for key, value in columns.iteritems():
                current_value = getattr(entry, key)
                args = (key, current_value, value)

                # only set the value if it has changed
                if value != current_value:
                    trans.log("setting %s from %s to %s" % args)
                    setattr(entry, key, value)
                    modified = True

            if modified:
                ids.append(entry.id)

        return ids
# end modify
