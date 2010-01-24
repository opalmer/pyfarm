'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 18 2010
PURPOSE: To setup, connect, and process a sqlite database

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

import sqlite3

class DB(object):
    '''
    Database object to handle all data reguarding PyFarm

    VARIABLES:
        db (file) -- Database to store information in by
        default it will write directly to memory.  This can
        be changed however:
            db="pyfarm.sql"
    '''
    def __init__(db=":memory:"):
        self.con = sqlite3.connect(db)
        self.sql = self.con.cursor()

    ####
    # all main statements start here
    ####

    def execute(self, statement):
        '''Commit any recent actions to the database'''
        self.sql.execute(statement)
        self.sql.commit()

    def dump(self, f):
        '''
        Dump the database to a file

        VARIABLES:
            f (file) -- file to write database to
        '''
        db = open(f,'w')
        for entry in self.sql.iterdump():
            db.write("%s\n" % entry)
        db.close()

    def close(self):
        '''Close out the database and connection objects'''
        pass
