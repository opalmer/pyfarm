'''
HOMEPAGE: www.pyfarm.net
INITIAL: Mar. 18 2011
PURPOSE: Contains the base exception for PyFarm

Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

class BaseException(Exception):
    '''Base PyFarm Exception'''
    def __init__(self, value):
        self.value = value

    def __str__(self):
        return repr(self.value)