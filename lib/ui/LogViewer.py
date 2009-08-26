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
# Created: Tue Aug 25 19:09:21 2009
#      by: PyQt4 UI code generator 4.4.4
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_LogViewer(object):
    def setupUi(self, LogViewer):
        LogViewer.setObjectName("LogViewer")
        LogViewer.resize(571, 799)
        self.centralwidget = QtGui.QWidget(LogViewer)
        self.centralwidget.setObjectName("centralwidget")
        self.deleteLog = QtGui.QPushButton(self.centralwidget)
        self.deleteLog.setGeometry(QtCore.QRect(290, 700, 90, 27))
        self.deleteLog.setObjectName("deleteLog")
        self.log = QtGui.QTextBrowser(self.centralwidget)
        self.log.setGeometry(QtCore.QRect(11, 37, 551, 651))
        font = QtGui.QFont()
        font.setPointSize(10)
        self.log.setFont(font)
        self.log.setAcceptDrops(False)
        self.log.setObjectName("log")
        self.header = QtGui.QLabel(self.centralwidget)
        self.header.setGeometry(QtCore.QRect(11, 11, 551, 20))
        font = QtGui.QFont()
        font.setPointSize(12)
        font.setWeight(75)
        font.setBold(True)
        self.header.setFont(font)
        self.header.setAlignment(QtCore.Qt.AlignCenter)
        self.header.setObjectName("header")
        self.saveLog = QtGui.QPushButton(self.centralwidget)
        self.saveLog.setGeometry(QtCore.QRect(384, 707, 85, 27))
        self.saveLog.setObjectName("saveLog")
        self.autoRefresh = QtGui.QCheckBox(self.centralwidget)
        self.autoRefresh.setGeometry(QtCore.QRect(103, 709, 111, 23))
        self.autoRefresh.setObjectName("autoRefresh")
        self.closeButton = QtGui.QPushButton(self.centralwidget)
        self.closeButton.setGeometry(QtCore.QRect(475, 707, 85, 27))
        self.closeButton.setObjectName("closeButton")
        self.refreshNow = QtGui.QPushButton(self.centralwidget)
        self.refreshNow.setGeometry(QtCore.QRect(12, 707, 85, 27))
        self.refreshNow.setObjectName("refreshNow")
        self.refreshTime = QtGui.QSpinBox(self.centralwidget)
        self.refreshTime.setEnabled(False)
        self.refreshTime.setGeometry(QtCore.QRect(220, 707, 58, 26))
        self.refreshTime.setMinimum(1)
        self.refreshTime.setMaximum(600)
        self.refreshTime.setProperty("value", QtCore.QVariant(5))
        self.refreshTime.setObjectName("refreshTime")
        LogViewer.setCentralWidget(self.centralwidget)
        self.menubar = QtGui.QMenuBar(LogViewer)
        self.menubar.setGeometry(QtCore.QRect(0, 0, 571, 27))
        self.menubar.setObjectName("menubar")
        LogViewer.setMenuBar(self.menubar)
        self.statusbar = QtGui.QStatusBar(LogViewer)
        self.statusbar.setObjectName("statusbar")
        LogViewer.setStatusBar(self.statusbar)

        self.retranslateUi(LogViewer)
        QtCore.QObject.connect(self.closeButton, QtCore.SIGNAL("clicked()"), LogViewer.close)
        QtCore.QMetaObject.connectSlotsByName(LogViewer)
        LogViewer.setTabOrder(self.log, self.refreshNow)
        LogViewer.setTabOrder(self.refreshNow, self.autoRefresh)
        LogViewer.setTabOrder(self.autoRefresh, self.refreshTime)
        LogViewer.setTabOrder(self.refreshTime, self.deleteLog)
        LogViewer.setTabOrder(self.deleteLog, self.saveLog)
        LogViewer.setTabOrder(self.saveLog, self.closeButton)

    def retranslateUi(self, LogViewer):
        LogViewer.setWindowTitle(QtGui.QApplication.translate("LogViewer", "Log Viewer", None, QtGui.QApplication.UnicodeUTF8))
        self.deleteLog.setText(QtGui.QApplication.translate("LogViewer", "Delete Log", None, QtGui.QApplication.UnicodeUTF8))
        self.header.setText(QtGui.QApplication.translate("LogViewer", "Output Log", None, QtGui.QApplication.UnicodeUTF8))
        self.saveLog.setText(QtGui.QApplication.translate("LogViewer", "Save Log", None, QtGui.QApplication.UnicodeUTF8))
        self.autoRefresh.setText(QtGui.QApplication.translate("LogViewer", "Auto Refresh", None, QtGui.QApplication.UnicodeUTF8))
        self.closeButton.setText(QtGui.QApplication.translate("LogViewer", "Close", None, QtGui.QApplication.UnicodeUTF8))
        self.refreshNow.setText(QtGui.QApplication.translate("LogViewer", "Refresh", None, QtGui.QApplication.UnicodeUTF8))
        self.refreshTime.setSuffix(QtGui.QApplication.translate("LogViewer", "s", None, QtGui.QApplication.UnicodeUTF8))

