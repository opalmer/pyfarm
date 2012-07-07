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

'''Provides functions and classes for transaction management'''

import time

import sqlalchemy
import sqlalchemy.orm
import sqlalchemy.schema
from twisted.python import log

from pyfarm import logger, prefs
from pyfarm.db import session

class Transaction(object):
    '''
    context manager for transactions

    :param sqlalchemy.Table table:
        the table to perform the query on

    :param object base:
        optional base to map query onto
    '''
    def __init__(self, table, base=None, system=None):
        class BaseObject(object):
            pass
        # end BaseOjbect
        self.__system = system

        if not isinstance(table, sqlalchemy.schema.Table):
            raise TypeError("unexpected type %s for table" % type(table))

        self.base = base or BaseObject
        self.table = table
        self.tablename = self.table.fullname

        # single engine per run (this MAY be needed later)
        self.engine = session.ENGINE
        self.Session = sqlalchemy.orm.scoped_session(session.Session)
#        sqlalchemy.orm.sessionmaker()

    # end __init__

    def log(self, msg, level='SQL'):
        log.msg(msg, level=level, system=self.__system or 'Transaction')
    # end

    def __enter__(self):
        self.start = time.time()
        self.log("opening database transaction on %s" % self.tablename)

        # map the object and prepare the session
        sqlalchemy.orm.mapper(self.base, self.table)
        self.session = self.Session()
        self.query = self.session.query(self.base)

        return self
    # end __enter__

    def __exit__(self, exc_type, exc_val, exc_tb):
        # roll back the transaction in the event of an error
        if exc_type is not None:
            self.log("rolling back database transaction: %s" % exc_val)
            self.session.rollback()

        # commit changes if there are any pending
        else:
            if self.session.dirty or self.session.new:
                self.session.commit()
                log.msg("committing database entry to %s" % self.tablename)

        # close the session
        self.query.session.close()

        # close the database connection
        if prefs.get('database.setup.close-connections'):
            self.log("closing connections")
            self.query.session.bind.dispose()

        self.end = time.time()
        args = (self.tablename, self.end-self.start)
        self.log("closed database transaction on %s (%ss)" % args)
    # end __exit__
# end Transaction


class Connection(object):
    '''manages a single connection to the database'''
    def __init__(self, connection=None):
        self.connection = connection
    # end __init__

    def __enter__(self):
        if self.connection is None:
            self.connection = session.ENGINE.connect()

        return self.connection
    # end __enter__

    def __exit__(self, exc_type, exc_val, exc_tb):
        self.connection.close()
        session.ENGINE.dispose()
    # end __exit__
# end Connection
