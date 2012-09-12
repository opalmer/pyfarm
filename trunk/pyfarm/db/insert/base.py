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

'''base function used for performing insertions'''

import logging
import sqlalchemy

from pyfarm import errors
from pyfarm.db import session

from twisted.python import log

class Insert(object):
    '''thin wrapper class around insertion statements'''
    def __init__(self, table):
        self.table = table
        self.data = []
        self.results = None

        if not isinstance(self.table, sqlalchemy.Table):
            raise TypeError("table must be a sqlalchemy.Table object")
    # end __init___

    def add(self, data):
        self.data.append(data)
    # end add

    def __enter__(self):
        return self
    # end __enter__

    def __exit__(self, exc_type, exc_val, exc_tb):
        if exc_type is not None:
            log.msg(
                "exception occurred, cannot insert entries",
                level=logging.WARNING
            )
            return

        elif not self.data:
            log.msg("nothing to commit", level=logging.WARNING)

        # create a connection and insert all entries
        # at once
        connection = session.ENGINE.connect()

        with connection.begin():
            result = connection.execute(self.table.insert(), self.data)

            if hasattr(result, 'inserted_primary_key'):
                self.results = result.inserted_primary_key
            else:
                self.results = result.last_inserted_ids()

            if not self.results:
                raise errors.InsertionFailure(self.data, self.table)

        # close the connection
        connection.close()
        session.ENGINE.dispose()
    # end __exit__
# end Insert

def insert(table, match_name, data, error=True, drop=False):
    '''
    performs a single insertion will providing some validation to ensure


    :param sqlalchemy.Table table:
        the table

    :param string match_name:
        the column name we will use to determine if the entry is unique

    :param dict data:
        the data to insert

    :exception KeyError:
        raised if
    :return:
        None or the newly inserted id
    '''
    match_column = getattr(table.c, match_name)
    match_entry = data[match_name]

    # find any existing entries in the provided table
    select = table.select(match_column == match_entry)
    existing_entries = select.execute().fetchall()
    entry_count = len(existing_entries)

    if entry_count:
        if not drop and error:
            raise errors.DuplicateEntry(match_name, match_entry, table)

        elif not error:
            args = (match_entry, table)
            msg = "found existing entries for %s in %s, skipping" % args
            log.msg(msg, level=logging.WARNING)
            return

        else:
            args = (match_name, match_entry, table)
            msg ="dropping entries of %s %s in %s" % args
            log.msg(msg, level=logging.WARNING)

            # delete all entries we found using their id
            for entry in existing_entries:
                args = (match_name, getattr(entry, match_name), table)
                log.msg("removing %s %s from %s" % args)
                delete = table.delete(table.c.id == entry.id)
                delete.execute()
    else:
        with Insert(table) as insert:
            insert.add(data)

        return insert.results
# end insert
