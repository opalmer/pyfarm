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

from sqlalchemy.ext.declarative import declarative_base

from pyfarm.logger import Logger
logger = Logger(__name__)

# import the engine, base, and required constants
from pyfarm.db.engines import engine
from pyfarm.db.tables._baseclass import PyFarmBase
from pyfarm.db.tables._constants import *

# create the base object using the proper engine and base class
Base = declarative_base(cls=PyFarmBase, bind=engine)

# import all the table objects
from pyfarm.db.tables.frame import Frame
from pyfarm.db.tables.master import Master
from pyfarm.db.tables.host import Host, HostGroup, HostSoftware
from pyfarm.db.tables.job import Job
from pyfarm.db.tables.dependency import F2FDependency, J2JDependency

def init(rebuild=False):
    """
    initializes the tables according the the preferences, rebuilding
    if requested
    """
    if rebuild or DB_REBULD:
        logger.warning('dropping all tables before rebuilding')
        Base.metadata.reflect()
        Base.metadata.drop_all()

    Base.metadata.create_all()
# end init
