'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 28 2010
PURPOSE: To query and return information about the local system

This file is part of PyFarm.
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
import os
import sys
import fnmatch

def clean(option=None, opt=None, value=None, parser=None):
    '''Remove all pyc files (and any other tmp files'''
    for dirpath, dirnames, files in os.walk(PYFARM):
        if not fnmatch.fnmatch(dirpath, "*.git*"):
            for dirname in dirnames:
                if not fnmatch.fnmatch(dirname, "*.git*"):
                    for filename in files:
                        path = os.path.join(dirpath, dirname, filename)
                        if path.endswith(".pyc") and os.path.isfile(path):
                            os.remove(path)

def cleanAll(option=None, opt=None, value=None, parser=None):
    '''Cleanup all files including pyc, database, and lock file'''
    clean('','','','')

    db = os.path.join(PYFARM, "PyFarmDB.sql")
    if os.path.isfile(db):
        os.remove(db)
