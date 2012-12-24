# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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

from pyfarm.logger import Logger
from pyfarm.preferences import prefs
from pyfarm.db import session

class Session(Logger):
    '''
    context manager for for queries and modifications

    :param sqlalchemy.Table table:
        the table to perform the query on

    :param object base:
        optional base to map query onto
    '''
    def __init__(self, table, base=None, system=None,
                 close_connections=prefs.get('database.setup.close-connections')
        ):
        class Entry(object):
            '''
            base object which represents all results and has a "nice"
            string representation.  This class must be defined in __init__
            otherwise the same class will be mapped to multiple tables
            resulting in an ArgumentError
            '''
            def __repr__(self):
                # iterate over all variables loaded onto self
                # and generate a meaningful string representation
                values = []
                for name, value in vars(self).iteritems():
                    if not name.startswith("_"):
                        if isinstance(value, (str, unicode)):
                            value = "%s='%s'" % (name, value)
                        else:
                            value = "%s=%s" % (name, value)

                        values.append(value)

                return "%s(%s)" % (self.__class__.__name__, (", ".join(values)))
            # end __repr__
        # end Entry

        Logger.__init__(self, system or self.__class__.__name__)

        if not isinstance(table, sqlalchemy.schema.Table):
            raise TypeError("unexpected type %s for table" % type(table))

        self.base = base or Entry
        self.table = table
        self.tablename = self.table.fullname
        self.close_connections = close_connections

        # single engine per run (this MAY be needed later)
        self.engine = session.ENGINE
        self.Session = sqlalchemy.orm.scoped_session(session.Session)
    # end __init__

    def __enter__(self):
        self.start = time.time()

        # map the object and prepare the session
        sqlalchemy.orm.mapper(self.base, self.table)
        self.session = self.Session()
        self.query = self.session.query(self.base)

        return self
    # end __enter__

    def __exit__(self, exc_type, exc_val, exc_tb):
        # roll back the transaction in the event of an error
        if exc_type is not None:
            self.debug("rolling back database transaction: %s" % exc_val)
            self.session.rollback()

        # commit changes if there are any pending
        else:
            if self.session.dirty or self.session.new:
                self.session.commit()
                self.debug("committing database entry to %s" % self.tablename)

        # close the session
        self.query.session.close()

        # close the database connection
        if  self.close_connections:
            self.query.session.bind.dispose()

        self.end = time.time()
        args = (self.tablename, self.end-self.start)
        self.debug("closed database transaction on %s (%ss)" % args)
    # end __exit__
# end Transaction


class Connection(Logger):
    '''manages a single connection to the database'''
    def __init__(self, connection=None):
        Logger.__init__(self, self)
        self.connection = connection
    # end __init__

    def __enter__(self):
        self.start = time.time()
        if self.connection is None:
            self.connection = session.ENGINE.connect()

        return self.connection
    # end __enter__

    def __exit__(self, exc_type, exc_val, exc_tb):
        self.connection.close()
        session.ENGINE.dispose()
        self.debug("closed connections, %s elapsed" % (time.time()-self.start))
    # end __exit__
# end XMLRPCConnection
