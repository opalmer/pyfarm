'''
HOMEPAGE: www.pyfarm.net
INITIAL: July 2 2010
PURPOSE: To read, write, and create the initial database entries

    This file is part of PyFarm.
    Copyright 2008-2010 (C) Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys
from xml.dom.minidom import parse

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

class DBSetup(object):
    '''Parse the database xml file and setup the initial database'''
    def __init__(self, xml, db):
        self.xml = parse(xml)
        self.db = sqlite3.connect(db)
        self.cur = self.db.cursor()
        self.type = {
                            'none' : 'NULL',
                            'int' : 'INTEGER',
                            'long' : 'INTEGER',
                            'str' : 'TEXT',
                            'unicode' : 'TEXT',
                            'buffer' : 'BLOB',
                        }

        script = ''
        createTable = 'CREATE TABLE IF NOT EXISTS '
        for table in self.tables():
            name = table.getAttribute("name")
            statement = createTable
            statement += name
            statement += self.columns(table)

            # so long as the table is not 'empty' create it
            if not statement == '%s%s();' % (createTable,  name):
                script += statement

            self.cur.executescript(script)
            self.cur.close()

    def tables(self):
        '''Iterator function to return the tables'''
        for table in self.xml.getElementsByTagName("table"):
            yield table

    def columns(self, table):
        '''Function to turn xml column information into string'''
        out = '('
        for node in table.childNodes:
            if node.nodeType == 1:
                name = node.getAttribute("name")
                type = self.type[node.getAttribute("type")]
                out += "%s %s," % (name,  type)

        if not out == '(':
            return out[:len(out)-1] + ');'
        else:
            return '();'

def Setup(xml, db=':memory:'):
    '''
    Instead of requiring access to .db we will return that
    individual attribute instead.
    '''
    return DBSetup(xml, db).db

def InitDb(dbFile,  xml='SOME_DEFAULT'):
    '''Populate the database at the given path, do not return the sqlite3 object'''
    return None

def Import(db, dbfile):
    '''
    Import a database from disc into the given database

    VARIABLES:
        db (sqlite3.Connection) -- Currently active database to insert tables into
        dbfile (string) -- Path to database to import from
    '''
    # only if dbfile is actually a file should we continue
    if os.path.isfile(dbfile):
        pass
    else:
        raise IOError("%s does not exist!" % dbfile)

def Export(db, dbfile):
    '''

    Export the given database to disc

    VARIABLES:
        db (sqlite3.Connection) -- Currently active database to export tables from
        dbfile (string) -- Path to export tables to
    '''
    parentDir = os.path.dirname(dbfile)

    # check to make sure the output directory exists
    #  If it does not create it, then continue
    if not os.path.isdir(parentDir):
        os.mkdirs(parentDir)

    # dump the database
    dump = os.linesep.join(db.iterdump())
    f = open(dbfile,  'w')
    f.writelines(dump)
    f.close()
