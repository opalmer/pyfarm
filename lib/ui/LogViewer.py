'''
    This file is part of PyFarm.
    Copyright (C) 2008-2009 Oliver Palmer

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
# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'QtDesigner/LogViewer.ui'
#
# Created: Sun Apr 12 17:23:06 2009
#      by: PyQt4 UI code generator 4.4.2
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_LogViewer(object):
    def setupUi(self, LogViewer):
        LogViewer.setObjectName("LogViewer")
        LogViewer.resize(602,709)
        self.groupBox = QtGui.QGroupBox(LogViewer)
        self.groupBox.setGeometry(QtCore.QRect(20,10,561,651))
        font = QtGui.QFont()
        font.setPointSize(12)
        self.groupBox.setFont(font)
        self.groupBox.setObjectName("groupBox")
        self.log = QtGui.QTextBrowser(self.groupBox)
        self.log.setGeometry(QtCore.QRect(10,30,541,611))
        font = QtGui.QFont()
        font.setPointSize(10)
        self.log.setFont(font)
        self.log.setObjectName("log")
        self.closeLogViewer = QtGui.QPushButton(LogViewer)
        self.closeLogViewer.setGeometry(QtCore.QRect(500,670,80,28))
        self.closeLogViewer.setObjectName("closeLogViewer")

        self.retranslateUi(LogViewer)
        QtCore.QObject.connect(self.closeLogViewer,QtCore.SIGNAL("pressed()"),LogViewer.close)
        QtCore.QMetaObject.connectSlotsByName(LogViewer)

    def retranslateUi(self, LogViewer):
        LogViewer.setWindowTitle(QtGui.QApplication.translate("LogViewer", "Render Log View", None, QtGui.QApplication.UnicodeUTF8))
        self.groupBox.setTitle(QtGui.QApplication.translate("LogViewer", "Frame Log", None, QtGui.QApplication.UnicodeUTF8))
        self.closeLogViewer.setText(QtGui.QApplication.translate("LogViewer", "Close", None, QtGui.QApplication.UnicodeUTF8))

