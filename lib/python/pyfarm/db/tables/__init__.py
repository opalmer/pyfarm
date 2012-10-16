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

#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

from sqlalchemy.ext.declarative import declarative_base

from pyfarm.logger import Logger
from pyfarm.preferences import prefs
from pyfarm.db.tables.base import PyFarmBase, engine

# TODO: remove test code
from sqlalchemy import create_engine
engine = create_engine("sqlite:///:memory:", echo=False)

logger = Logger(__name__)
Base = declarative_base(cls=PyFarmBase, bind=engine)

from pyfarm.db.tables.frame import Frame
from pyfarm.db.tables.host import Host, Group, Software
from pyfarm.db.tables.job import Dependency, Job

def init(rebuild=False):
    '''
    initializes the tables according the the preferences, rebuilding
    if requested
    '''
    if rebuild or prefs.get('database.setup.rebuild'):
        logger.warning('dropping all tables before rebuilding')
        Base.metadata.reflect()
        Base.metadata.drop_all()

    Base.metadata.create_all()
# end init
