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

"""manager of sqlalchemy session objects"""

from sqlalchemy.orm import sessionmaker
from pyfarm.db.engines import engine


class SessionManager(object):
    """
    Manages the connection objects and produces
    sessions.
    """
    SESSION_MAKER = sessionmaker()
    ENGINES = {'default' : engine}

    @classmethod
    def engine(cls, name='default'):
        """returns the requested engine for the provided name"""
        name = 'default' if name is None else name
        try:
            return cls.ENGINES[name]
        except KeyError:
            raise NameError("unknown connection engine %s" % repr(name))
    # end engine

    @classmethod
    def session(cls, name='default', **sessionkwargs):
        """
        Returns a new session object and creates a connection if
        necessary
        """
        sessionkwargs.setdefault('bind', cls.engine(name))
        return cls.SESSION_MAKER(**sessionkwargs)
    # end session
# end SessionManager
