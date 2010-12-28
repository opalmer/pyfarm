'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 19 2010
PURPOSE: To perform general operations such as setup on a sqlite database

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
import sys
import sqlite3
from xml.dom import minidom

CWD      = os.path.dirname(os.path.abspath(__file__))
PYFARM   = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE   = os.path.basename(__file__)
DB_XML   = os.path.join(PYFARM, "cfg", "dbbase.xml")
LOGLEVEL = 2
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import Logger

log = Logger.Logger(MODULE, LOGLEVEL)

def DBSetup(dbFile=":memory:", new=False):
    '''Setup the database and the default tables'''
    if new and os.path.isfile(dbFile):
        log.info("Removing old database: %s" % dbFile)
        os.remove(dbFile)

    log.info("Parsing DB XML: %s" % DB_XML)
    xml = minidom.parse(DB_XML)
    sql = sqlite3.connect(dbFile)
    db  = sql.cursor()

    for element in xml.getElementsByTagName("table"):
        script    = ""
        tableName = element.getAttribute("name")
        script += "CREATE TABLE IF NOT EXISTS %s (" % tableName

        # iterate over each columns
        columns = []
        for column in element.getElementsByTagName("column"):
            colName = column.getAttribute("name")
            colType = column.getAttribute("type")
            columns.append("%s %s" % (colName, colType))

        # create the table, if we have added columns
        if len(columns):
            log.info("Creating table %s" % tableName)
            script += ",".join(columns)
            script += ");"

            # execute the generated script
            db.executescript(script)
            sql.commit()

    # close the table
    db.close()
    return dbFile

def DBDump(db, location):
    '''Dump the given database to a location'''
    pass

if __name__ != "__MAIN__":
    sql = os.path.join(PYFARM, "PyFarmDB.sql")
    DBSetup(sql, new=True)