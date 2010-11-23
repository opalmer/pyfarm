'''
HOMEPAGE: www.pyfarm.net
INITIAL: Nov 21 2010
PURPOSE [FOR DEVELOPMENT PURPOSES ONLY]:
    Read and return information about screen
    sessions.

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

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

import os

USER = os.getenv('USER')

class ScreenSession(object):
    '''
    Return information about a specific screen
    session
    '''
    def __init__(self, id):
        self.pid = id.split('.')[0]
        self.name = id.split('.')[1]

    def __repr__(self):
        return self.name


def sessions():
    '''Return a list of screen session objects'''
    for screen in os.listdir('/var/run/screen/S-%s' % USER):
        yield ScreenSession(screen)