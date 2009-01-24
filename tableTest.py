#!/usr/bin/python

import sys
from PyQt4.QtCore import *
from PyQt4.QtGui import *

from lib.Network import *


class MyTable(QTableWidget):
    def __init__(self, parent=None, *args):
        # global setup
        super(MyTable, self).__init__(parent)
        self.setAlternatingRowColors(True)
        self.setSelectionMode(QAbstractItemView.ContiguousSelection)
        self.setSelectionBehavior(QAbstractItemView.SelectRows)
        self.setEditTriggers(QAbstractItemView.NoEditTriggers)

        # set header labels
        self.setHeader(['Hostname', 'IP Address', 'Status'])

        # add hosts
        self.hostList = ['quadro', 'master', 'render01', \
                                'render02', 'render03', 'render04']
        for host in self.hostList:
            self.addHost(ResolveHost(host))

    def addHost(self, host):
        '''Add the given host to the table'''
        y = 0
        x = self.rowCount()
        host.append('Inactive')
        self.insertRow(self.rowCount())

        for attribute in host:
            item = QTableWidgetItem(attribute)
            self.setItem(x, y, item)
            y += 1

    def setHeader(self, labels):
        '''Set the header labels'''
        position = 0
        self.setColumnCount(len(labels))

        for label in labels:
            labelItem = QTableWidgetItem(label)
            self.setHorizontalHeaderItem(position, labelItem)
            position += 1

if __name__=="__main__":
    app = QApplication(sys.argv)
    table = MyTable()
    table.show()
    table.resize(400, 600)
    sys.exit(app.exec_())
