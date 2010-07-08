'''
HOMEPAGE: www.pyfarm.net
INITIAL: July 2 2010
PURPOSE: To read, write, and create the initial database entries

    This file is part of PyFarm.
    Copyright 2008-2010 (C) Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

import os
import os.path
import sqlite3

def Import(activeDb, dbfile):
    '''
    Import a database from disc into the given database

    VARIABLES:
        activeDb (tbd) -- Currently active database to insert tables into
        dbfile (string) -- Path to database to import from
    '''
    # only if dbfile is actually a file should we continue
    if os.path.isfile(dbfile):
        pass
    else:
        raise IOError("%s does not exist!" % dbfile)

def Export(activeDb, dbfile):
    '''

    Export the given database to disc

    VARIABLES:
        activeDb (tbd) -- Currently active database to export tables from
        dbfile (string) -- Path to export tables to
    '''
    parentDir = os.path.dirname(dbfile)

    # check to make sure the output directory exists
    #  If it does not create it, then continue
    if not os.path.isdir(parentDir):
        os.mkdirs(parentDir)

def Setup(db):
    '''
    Setup and prepopulate the given database

    VARIABLES:
        db (string) -- location of databse to populate
    '''
    pass
