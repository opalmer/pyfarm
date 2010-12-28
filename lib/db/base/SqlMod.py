'''
HOMEPAGE: www.pyfarm.net
INITIAL: July 2 2010
PURPOSE: To modify entries in the given database

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
import sqlite3

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
LOGLEVEL = 2
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import Logger

log = Logger.Logger(MODULE, LOGLEVEL)
log.deprecated("Outdated module")
class Insert(object):
    '''
    Main wrapper class used to insert entries into a database

    VARIABLES:
        db (sqlite3.Connection) -- Database to operate on
        table (str) -- table to operate on
    '''
    def __init__(self, db,  table):
        self.action = "INSERT"
        self.table = table


class Update(object):
    '''
    Main wrapper class used to replace entries in the database


    VARIABLES:
        db (sqlite3.Connection) -- Database to operate on
        table (str) -- table to operate on
    '''
    def __init__(self,  db, table):
        self.action = "UPDATE"
        self.table = table


class Delete(object):
    '''
    Main wrapper class used to delete entries from a database


    VARIABLES:
        db (sqlite3.Connection) -- Database to operate on
        table (str) -- table to operate on
    '''
    def __init__(self,  db, table):
        self.action = "DELETE"
        self.table = table
