'''
HOMEPAGE: www.pyfarm.net
INITIAL: Dec 26 2010
PURPOSE: To handle the setup and maintenance of the network table

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

CWD      = os.path.dirname(os.path.abspath(__file__))
PYFARM   = os.path.abspath(os.path.join(CWD, "..",  ".."))
MODULE   = os.path.basename(__file__)
LOGLEVEL = 2
if PYFARM not in sys.path: sys.path.append(PYFARM)

from PyQt4.Qt import Qt
from PyQt4 import QtSql, QtCore

from lib import logger

# setup logging
log = logger.Logger(MODULE, LOGLEVEL)

class Manager(object):
    '''
    SqlTable manager for QTableView objects.

    VARIABLES:
        db        (QSqlDatabase) -- database to operate on
        ui        (QTableView)   -- Table to add sql model to
        tableName (str)          -- Name of database to run query on
        path      (str)          -- Location of sql file (or special memory string)
        columns   (list)         -- Columns to retrieve from table
        sort      (str)          -- Column to sort table by
    '''
    def __init__(self, db, ui, table, columns, sort=None):
        self.db        = db
        self.ui        = ui
        self.columns   = columns
        self.query     = "SELECT %s FROM %s" % (','.join(columns), table)
        self.sqlModel  = QtSql.QSqlTableModel()

        self.sqlQuery  = QtSql.QSqlQuery(self.query, self.db)
        self.sqlModel.setQuery(self.sqlQuery)
        self.sqlModel.setTable(table)

        # create global column variables
        i = 0
        for column in columns:
            vars()[column] = i
            i += 1

        # final setup
        self.sqlModel.setEditStrategy(QtSql.QSqlTableModel.OnManualSubmit)
        self.select = self.sqlModel.setQuery(self.sqlQuery)

        # set header data
        direction  = QtCore.Qt.Horizontal
        for column in columns:
            label  = QtCore.QVariant(column.lower().capitalize())
            colNum = vars()[column]
            self.sqlModel.setHeaderData(colNum, direction, label)

        # set sorting, if a sort column was given
        if sort:
            self.sqlModel.setSort(vars()[sort], QtCore.Qt.AscendingOrder)

        # set interface's model
        self.ui.setModel(self.sqlModel)

    def refresh(self):
        '''
        Refresh the host table and reselect any previous items in
        the best manner possible
        '''
        # keyboardSearch
        indexes = self.ui.selectedIndexes()
        colA    = None
        if len(indexes):
            for index in indexes:
                if index.column() == 0 and not colA:
                    colA = index.data().toString()

        self.sqlModel.select()

        log.fixme("Refresh fails to always reselect previous selection")
        log.fixme("...check to ensure we have the correct column")
        log.fixme("...wait if we do not")
        log.fixme("...might also have to do with HOW the selection is made")

        if type(colA) != None:
            self.ui.keyboardSearch(colA)
