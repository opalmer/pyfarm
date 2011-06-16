# No shebang line, this module is meant to be imported
#
# INITIAL: Nov 21 2010
# PURPOSE: Read and return information about screen sessions
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

import os
import fnmatch

USER = os.getenv('USER')

class ScreenSession(object):
    '''Return information about a specific screen session'''
    def __init__(self, id):
        self.pid = id.split('.')[0]
        self.name = id.split('.')[1]

    def __repr__(self):
        return self.name

def sessions(match='*.PyFarm'):
    '''Return a list of screen session objects'''
    try:
        sessions = os.listdir('/var/run/screen/S-%s' % USER)

    except OSError, error:
        print "Could retrieve session list: %s" % error

    else:
        for session in fnmatch.filter(sessions, match):
            yield ScreenSession(session)

def isRunning():
    '''Return True if you can find a running screen process'''
    return len(list(sessions()))

def start(script):
    '''Start a screen process'''
    #script = "/home/opalmer/test.py"
    os.system("screen -h 100 -dmS PyFarm python %s" % script)
