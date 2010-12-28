'''
HOMEPAGE: www.pyfarm.net
INITIAL: Dec 27 2010
PURPOSE: Minor functions to be used by the entire package when lib is imported

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
from xml.dom import minidom

from PyQt4 import QtSql, QtCore

CWD      = os.path.dirname(os.path.abspath(__file__))
PYFARM   = os.path.abspath(os.path.join(CWD, "..", ".."))
MODULE   = os.path.basename(__file__)
DB_XML   = os.path.join(PYFARM, "cfg", "dbbase.xml")
LOGLEVEL = 2
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import Logger

log = Logger.Logger("db.includes.py", LOGLEVEL)

def connect(dbFile=DB_XML, clean=False):
    '''
    Connect to the given database file and ensure all initial
    conditions and required tables are met.
    '''
    if clean and os.path.isfile(dbFile):
        log.warning("Removing File: %s" % dbFile)
        os.remove(dbFile)

    db = QtSql.QSqlDatabase.addDatabase("QSQLITE")
    db.setDatabaseName(dbFile)

    if db.open():
        xml   = minidom.parse(DB_XML)
        query = QtSql.QSqlQuery(db)

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
                script += ",".join(columns)
                script += ");"

                # execute the generated script
                result = query.exec_(QtCore.QString(script))
                if result:
                    log.info("Creating table %s" % tableName)
        return db

    else:
        return db.lastError()