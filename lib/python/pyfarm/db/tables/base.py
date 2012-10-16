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

from itertools import izip

from sqlalchemy.types import Integer
from sqlalchemy import Column, create_engine
from sqlalchemy.exc import OperationalError

from pyfarm.logger import Logger
from pyfarm.errors import ConfigurationError
from pyfarm.datatypes.enums import State
from pyfarm.preferences import prefs

logger = Logger(__name__)

class PyFarmBase(object):
    '''
    base class which defines some base functions and attributes
    for all classes to inherit
    '''
    repr_attrs = ()

    # base column definitions which all other classes inherit
    id = Column(Integer, primary_key=True, autoincrement=True)

    def __repr__(self):
        values = []

        for attr in ( attr for attr in self.repr_attrs if hasattr(self, attr) ):
            original_value = getattr(self, attr)

            if attr == 'state':
                value = State.get(original_value)
            elif isinstance(original_value, unicode):
                value = "'%s'"  % original_value
            else:
                value = repr(original_value)

            values.append("%s=%s" % (attr, value))

        return "%s(%s)" % (self.__class__.__name__, ", ".join(values))
    # end __repr__
# end PyFarmBase

# zip up the configurations and the urls and
# iterate over them till we find one we can use
zipconfig = izip(prefs.get('database.setup.config'), prefs.get('database.urls'))
for config, url in zipconfig:
    engine = create_engine(
        url,
        echo=prefs.get('logging.sqlalchemy.echo'),
        echo_pool=prefs.get('logging.sqlalchemy.pool')
    )

    try:
        # test to see if we can use this config to connect to
        # the database
        engine.connect()
        logger.info("connected to database using config: %s" % config)
        break

    except OperationalError:
        logger.warning("failed to connect with config: %s" % config)
        continue

else:
    raise ConfigurationError(msg="failed to connect to database using any config")
