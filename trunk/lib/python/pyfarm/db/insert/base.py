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

from __future__ import with_statement

import time
import logging
import sqlalchemy
from sqlalchemy import orm

from pyfarm import errors
from pyfarm.db import session
from pyfarm.datatypes.enums import SQL_TYPES
from pyfarm.logger import LoggingBaseClass

from twisted.python import log

class Insert(LoggingBaseClass):
    '''
    Base insertion class which performs commit on a batch basis while
    performing type and column checking as each entry is added.  See below
    for example use case:

    >>> with Insert(tables.foobar) as submit:
    >>>     data = {"bool" : True, "int" : 1, "string" : "hello world"}
    >>>     submit.add(checkduplicate=False, **data)
    >>>     print submit.results
    [Row(int=1, bool=True, id=64793, string=u'hello world')]

    :param sqlalchmey.Table table:
        the table definition we will be type checking against
        and using for insertions

    :param boolean getresults:
        if True then populate self.results when the resulting column ids
        after the commit

    '''
    count = property(lambda self: len(self.data))

    def __init__(self, table, getresults=True):
        if not isinstance(table, sqlalchemy.Table):
            raise TypeError(
                "table argument must be an instance of sqlalchemy.Table"
            )

        self.data = []
        self.table = table
        self.results = []
        self.connection = None
        self.column_names = []
        self.column_types = {}
        self.getresults = getresults
        self.primary_key = None

        # create a list of column names, their expected type(s), and
        # retrieve the primary key
        for column in self.table.columns:
            self.column_types[column.name] = SQL_TYPES[column.type.__class__]

            if self.primary_key is None and column.primary_key:
                self.primary_key = column.name

        self.column_names = self.column_types.keys()
       # end __init__

    # pre/post commit and add methods which are meant
    # to be overridden by the subclass
    def precommit(self): pass
    def postcommit(self): pass
    def preadd(self, data): pass
    def postadd(self, data): pass

    def __enter__(self):
        return self
    # end __enter__

    def __exit__(self, exc_type, exc_val, exc_tb):
        '''calls self.commit() so long as no exceptions have been raised'''
        if exc_type is not None:
            log.msg(
                "exception occurred, cannot commit entries",
                level=logging.WARNING
            )

        else:
            self.commit()
    # end __exit__

    def add(self, checktype=True, checkcolumn=True, checkduplicate=True,
            **kwargs):
        '''
        Adds to self.data and may be expanded upon in subclasses.  This method
        will also provide some insurance that the data being committed to
        the database is valid based on the current table definition.

        :param boolean checktype:
            when True (default) check each value against expected type for the
            given column

        :param boolean checkcolumn:
            when True (default) check each column to ensure it is currently
            defined in the table definiion

        :param boolean checkduplicate:
            when True (default) check ensure the the values we are adding
            are not already pending to be committed

        :exception ValueError:
            raised if the columns you are attempting to add do not exist in the
            table or if the values have already been added before

        :exception TypeError:
            raised if the values argument is not a dictionary or if
            some of the column type provided will not work with the current
            table definition
        '''
        if not kwargs:
            raise ValueError("no data provided to add")

        self.preadd(kwargs)

        for key, value in kwargs.iteritems():
            if checkcolumn and key not in self.column_names:
                # ensure the provided key actually exists in
                # the current table definition
                raise ValueError(
                    "requested column '%s' does not exist in %s" % (key, self.table)
                )

            if checktype:
                # ensure the provided value is of the correct type when
                # compared to the current table definition
                if not isinstance(value, self.column_types[key]):
                    raise TypeError(
                        "invalid type for %s, expected %s" % (key, self.column_types[key])
                    )

        # duplicate checking
        if checkduplicate and kwargs in self.data:
            raise ValueError("%s has already been added" % kwargs)

        self.data.append(kwargs)
        self.postadd(kwargs)
    # end add

    def commit(self):
        '''commits the data to self.table'''
        if not self.data:
            self.log("no data to commit", level=logging.WARNING)
            return

        self.connection = session.ENGINE.connect()

        # if we're expecting to get
        if self.getresults:
            results_start = time.time()
            log.msg("retrieving all records and storing their primary keys")
            scoped_session = orm.scoped_session(session.Session)
            query = scoped_session.query(self.table)
            primary_keys = set([getattr(i, self.primary_key) for i in query])
            log.msg("...%s" % (time.time()-results_start))

        self.precommit()

        with self.connection.begin() as trans:
            self.log("inserting %i records into %s" % (self.count, self.table))
            start = time.time()
            trans.connection.execute(self.table.insert(), self.data)
            trans.commit()

        args = (self.count, (time.time()-start), self.table)
        msg = "committed %i entries in %ss to %s" % args
        self.log(msg, level=logging.INFO)

        if self.getresults:
            results_start = time.time()
            log.msg("retrieving all records and calculating results")
            query = scoped_session.query(self.table)
            new_primary_keys = set([getattr(i, self.primary_key) for i in query])
            primary_column = getattr(self.table.c, self.primary_key)
            in_list = primary_column.in_(list(new_primary_keys-primary_keys))
            self.results = list(query.filter(in_list))
            log.msg("...%s" % (time.time()-results_start))

        self.postcommit()

        # close the connection
        self.connection.close()
        session.ENGINE.dispose()

        # remove the data we just committed
        del self.data[:]
    # end commit


    def close(self, conn=None):
        '''closes the given connection and clears self.data'''
        if conn is not None:
            conn.close()
            session.ENGINE.dispose()

        del self.data[:]
    # end close

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
            insert.add(**data)

        return insert.results
# end insert

