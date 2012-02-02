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

import types
from sqlalchemy import orm
from twisted.python import log

from session import Session

class Transaction(object):
    '''
    context manager for transactions

    :param sqlalchemy.Table table:
        the table to perform the query on

    :param object base:
        optional base to map query onto
    '''
    def __init__(self, table, base=None):
        class Base(object):
            pass
        # end Base

        self.base = base or Base
        self.table = table

        # map the object and prepare the session
        orm.mapper(self.base, self.table)
        self.session = Session()
        self.query = self.session.query(self.base)
    # end __init__

    def __enter__(self):
        log.msg("opening database transaction")
        return self
    # end __enter__

    def __exit__(self, type, value, trackback):
        # roll back the transaction in the event of an error
        if not isinstance(type, types.NoneType):
            log.msg("rolling back database transaction: %s" % value)
            self.session.rollback()
            return

        # commit changes if there are any pending
        if self.session.dirty or self.session.new:
            self.session.commit()
            log.msg("committing database entry")

        log.msg("closed database transaction")
    # end __exit__
# end Transaction
