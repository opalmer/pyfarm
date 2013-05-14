# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

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
