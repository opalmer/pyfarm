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
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

from sqlalchemy.ext.declarative import declarative_base

from pyfarm.logger import Logger
logger = Logger(__name__)

# import the engine, base, and required constants
from pyfarm.db.engine import engine
from pyfarm.db.tables._baseclass import PyFarmBase
from pyfarm.db.tables._constants import *

# create the base object using the proper engine and base class
Base = declarative_base(cls=PyFarmBase, bind=engine)

# import all the table objects
from pyfarm.db.tables.frame import Frame
from pyfarm.db.tables.master import Master
from pyfarm.db.tables.host import Host, HostGroup, HostSoftware
from pyfarm.db.tables.job import Dependency, Job

def init(rebuild=False):
    '''
    initializes the tables according the the preferences, rebuilding
    if requested
    '''
    if rebuild or DB_REBULD:
        logger.warning('dropping all tables before rebuilding')
        Base.metadata.reflect()
        Base.metadata.drop_all()

    Base.metadata.create_all()
# end init
