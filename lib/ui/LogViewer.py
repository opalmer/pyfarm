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
# Created: Sat May 16 20:01:38 2009
#      by: PyQt4 UI code generator 4.4.4
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_LogViewer(object):
    def setupUi(self, LogViewer):
        LogViewer.setObjectName("LogViewer")
        LogViewer.resize(630, 753)
        self.widget = QtGui.QWidget(LogViewer)
        self.widget.setGeometry(QtCore.QRect(10, 10, 601, 731))
        self.widget.setObjectName("widget")
        self.verticalLayout = QtGui.QVBoxLayout(self.widget)
        self.verticalLayout.setObjectName("verticalLayout")
        self.label = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setPointSize(12)
        font.setWeight(75)
        font.setBold(True)
        self.label.setFont(font)
        self.label.setObjectName("label")
        self.verticalLayout.addWidget(self.label)
        self.log = QtGui.QTextBrowser(self.widget)
        font = QtGui.QFont()
        font.setPointSize(10)
        self.log.setFont(font)
        self.log.setAcceptDrops(False)
        self.log.setObjectName("log")
        self.verticalLayout.addWidget(self.log)
        self.horizontalLayout = QtGui.QHBoxLayout()
        self.horizontalLayout.setObjectName("horizontalLayout")
        self.refreshNow = QtGui.QPushButton(self.widget)
        self.refreshNow.setObjectName("refreshNow")
        self.horizontalLayout.addWidget(self.refreshNow)
        self.autoRefresh = QtGui.QCheckBox(self.widget)
        self.autoRefresh.setObjectName("autoRefresh")
        self.horizontalLayout.addWidget(self.autoRefresh)
        self.refreshTime = QtGui.QSpinBox(self.widget)
        self.refreshTime.setEnabled(False)
        self.refreshTime.setMinimum(1)
        self.refreshTime.setMaximum(600)
        self.refreshTime.setProperty("value", QtCore.QVariant(5))
        self.refreshTime.setObjectName("refreshTime")
        self.horizontalLayout.addWidget(self.refreshTime)
        spacerItem = QtGui.QSpacerItem(94, 36, QtGui.QSizePolicy.Expanding, QtGui.QSizePolicy.Minimum)
        self.horizontalLayout.addItem(spacerItem)
        self.saveLog = QtGui.QPushButton(self.widget)
        self.saveLog.setObjectName("saveLog")
        self.horizontalLayout.addWidget(self.saveLog)
        self.closeButton = QtGui.QPushButton(self.widget)
        self.closeButton.setObjectName("closeButton")
        self.horizontalLayout.addWidget(self.closeButton)
        self.verticalLayout.addLayout(self.horizontalLayout)

        self.retranslateUi(LogViewer)
        QtCore.QObject.connect(self.closeButton, QtCore.SIGNAL("pressed()"), LogViewer.close)
        QtCore.QMetaObject.connectSlotsByName(LogViewer)

    def retranslateUi(self, LogViewer):
        LogViewer.setWindowTitle(QtGui.QApplication.translate("LogViewer", "Render Log View", None, QtGui.QApplication.UnicodeUTF8))
        self.label.setText(QtGui.QApplication.translate("LogViewer", "Output Log:", None, QtGui.QApplication.UnicodeUTF8))
        self.refreshNow.setText(QtGui.QApplication.translate("LogViewer", "Refresh", None, QtGui.QApplication.UnicodeUTF8))
        self.autoRefresh.setText(QtGui.QApplication.translate("LogViewer", "Auto Refresh", None, QtGui.QApplication.UnicodeUTF8))
        self.refreshTime.setSuffix(QtGui.QApplication.translate("LogViewer", "s", None, QtGui.QApplication.UnicodeUTF8))
        self.saveLog.setText(QtGui.QApplication.translate("LogViewer", "Save Log", None, QtGui.QApplication.UnicodeUTF8))
        self.closeButton.setText(QtGui.QApplication.translate("LogViewer", "Close", None, QtGui.QApplication.UnicodeUTF8))

