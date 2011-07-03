# No shebang line, this module is meant to be imported
#
# INITIAL: Jul 2 2011
# PURPOSE: To store general types to be used by the logger package
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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

class AttrDict(object):
    '''
    Special wrapper to provide access dictionary keys as attributes

    @param data: The data to wrap and read keys as attributes from
    @param alt: The alternate to provide if the key could not be found
    @param raiseError: If True raise an error when a key could not be found
    '''
    def __init__(self, data, alt=True, raiseError=True):
        self.data = data
        self.__alt = alt
        self.__raiseError = raiseError

    def __getattr__(self, attr):
        if self.__raiseError and not self.data.has_key(attr):
            raise AttributeError("No such key '%s' in data" % attr)

        return self.data.get(attr, self.__alt)