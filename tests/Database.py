'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 24 2010
PURPOSE: Main program to run and manage PyFarm

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

class DBSetup(object):
    '''
    Database object to handle all data reguarding PyFarm

    VARIABLES:
        db (file) -- Database to store information in by
        default it will write directly to memory.  This can
        be changed however:
            db="pyfarm.sql"
    '''
    def __init__(self,  db=":memory:"):
        self.db = sqlite3.connect(db)
        self.sql = self.db.cursor()

    def execute(self, statement):
        '''Commit any recent actions to the database'''
        self.sql.execute("""%s""" % statement)
        self.db.commit()
        return self.sql.fetchone()

    def createTable(self, tableName,  values):
        '''
        Create a table with the given table name

        VARIABLES:
            values (list) -- List of values to insert into table
                ex. ["status int", "software text"]
        '''
        statement = "CREATE TABLE IF NOT EXISTS %s (" % tableName
        maxLen = len(values)
        count = 1
        for value in values:
            statement += "%s" % value
            if count < maxLen:
                statement += ","
            count += 1
        statement += ")"
        self.execute(statement)

    def dump(self, f):
        '''
        Dump the database to a file

        VARIABLES:
            f (file) -- file to write database to
        '''
        db = open(f,'a')
        for entry in self.db.iterdump():
            db.write("%s\n" % entry)
        db.close()

    def close(self):
        '''Close the sql database'''
        self.sql.close()


def DumpDatabases(databases, dbFileStr):
    '''Given a set of database objects, dump them to storage'''
    for database in databases:
        database.dump(dbFileStr)

if __name__ == "__main__":
    import time
    import random

    # do this at script start
    db = DBSetup()
    db.execute("CREATE TABLE IF NOT EXISTS queue (job text, subjob text, status int, host text,\
                                                pid int, software text, start int, end int, frame int \
                                                command text, log text)")

#    for job in jobs:
#        subjobs = []
#        for num in range(1000, random.randint(1003, 1009)):
#            subjobs.append(hex(num))
#        for subjob in subjobs:
#            for frame in range(1, 5):
#                pass

    # the rest of the code is executed BY the user's actions
    db.execute("INSERT INTO queue VALUES ('Job A','0x7F9AD', 0, 'HostA', 3228, 'Maya 2008', 8742421, 8892525, 12, 'render -rv', '/path/to/file')")
#    print db.execute("SELECT * FROM stocks ORDER BY price")
#    db.dump("test.sql")
    db.close()
