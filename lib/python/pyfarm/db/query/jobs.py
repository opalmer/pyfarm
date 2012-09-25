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

'''
Provides common mechanisms for querying job related information
'''

from sqlalchemy import orm

from pyfarm.db.session import Session
from pyfarm.db.tables import jobs as _jobs


def exists(jobid):
    '''returns True if the given jobid exists'''
    session = orm.scoped_session(Session)
    return bool(session.query(_jobs).filter(_jobs.c.id == jobid).first())
# end exists
