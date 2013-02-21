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

"""
module for database specific preference handling
"""

from pyfarm.preferences.base.enums import NOTSET
from pyfarm.preferences.base.baseclass import Preferences

class DatabasePreferences(Preferences):
    """
    specialized class for handling preferences related to
    the database
    """
    def __init__(self):
        super(DatabasePreferences, self).__init__(filename='database')
    # end __init__

    def _url(self, config_name):
        """returns the url for the given configuration name"""
        data = self.get(config_name)
        print data
    # end _url

    def get(self, key, **kwargs):
        if isinstance(key, basestring) and key.endswith("urls"):
            configs = {}
            for config_name in self.get('setup.configs'):
                config_urls = configs.setdefault(config_name, [])
                for dbname in self.get('setup.configs.%s' % config_name):
                    if dbname not in config_urls:
                        url = self._url(dbname)
                        config_urls.append(url)
            return configs
        else:
            return super(DatabasePreferences, self).get(key, **kwargs)
    # end get
# end DatabasePreferences