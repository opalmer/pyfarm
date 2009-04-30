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

# Form implementation generated from reading ui file 'QtDesigner/HostInfo.ui'
#
# Created: Thu Apr 30 13:33:56 2009
#      by: PyQt4 UI code generator 4.3.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_HostInfo(object):
    def setupUi(self, HostInfo):
        HostInfo.setObjectName("HostInfo")
        HostInfo.resize(QtCore.QSize(QtCore.QRect(0,0,445,269).size()).expandedTo(HostInfo.minimumSizeHint()))

        self.closeButton = QtGui.QPushButton(HostInfo)
        self.closeButton.setGeometry(QtCore.QRect(350,236,80,28))
        self.closeButton.setObjectName("closeButton")

        self.groupBox = QtGui.QGroupBox(HostInfo)
        self.groupBox.setGeometry(QtCore.QRect(10,10,181,221))

        font = QtGui.QFont()
        font.setPointSize(12)
        self.groupBox.setFont(font)
        self.groupBox.setObjectName("groupBox")

        self.layoutWidget = QtGui.QWidget(self.groupBox)
        self.layoutWidget.setGeometry(QtCore.QRect(10,20,161,196))
        self.layoutWidget.setObjectName("layoutWidget")

        self.gridlayout = QtGui.QGridLayout(self.layoutWidget)
        self.gridlayout.setObjectName("gridlayout")

        self.label = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(75)
        font.setBold(True)
        self.label.setFont(font)
        self.label.setObjectName("label")
        self.gridlayout.addWidget(self.label,0,0,1,1)

        self.ipAddress = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.ipAddress.setFont(font)
        self.ipAddress.setObjectName("ipAddress")
        self.gridlayout.addWidget(self.ipAddress,0,2,1,3)

        self.label_2 = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(75)
        font.setBold(True)
        self.label_2.setFont(font)
        self.label_2.setObjectName("label_2")
        self.gridlayout.addWidget(self.label_2,1,0,1,2)

        self.hostname = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.hostname.setFont(font)
        self.hostname.setObjectName("hostname")
        self.gridlayout.addWidget(self.hostname,1,2,1,3)

        self.label_3 = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(75)
        font.setBold(True)
        self.label_3.setFont(font)
        self.label_3.setObjectName("label_3")
        self.gridlayout.addWidget(self.label_3,2,0,1,2)

        self.status = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.status.setFont(font)
        self.status.setObjectName("status")
        self.gridlayout.addWidget(self.status,2,2,1,3)

        self.label_4 = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(75)
        font.setBold(True)
        self.label_4.setFont(font)
        self.label_4.setObjectName("label_4")
        self.gridlayout.addWidget(self.label_4,3,0,1,2)

        self.os = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.os.setFont(font)
        self.os.setObjectName("os")
        self.gridlayout.addWidget(self.os,3,2,1,3)

        self.label_5 = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(75)
        font.setBold(True)
        self.label_5.setFont(font)
        self.label_5.setObjectName("label_5")
        self.gridlayout.addWidget(self.label_5,4,0,1,2)

        self.architecture = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.architecture.setFont(font)
        self.architecture.setObjectName("architecture")
        self.gridlayout.addWidget(self.architecture,4,2,1,3)

        self.label_6 = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(75)
        font.setBold(True)
        self.label_6.setFont(font)
        self.label_6.setObjectName("label_6")
        self.gridlayout.addWidget(self.label_6,5,0,1,2)

        self.rendered = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.rendered.setFont(font)
        self.rendered.setObjectName("rendered")
        self.gridlayout.addWidget(self.rendered,5,2,1,3)

        self.label_7 = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(75)
        font.setBold(True)
        self.label_7.setFont(font)
        self.label_7.setObjectName("label_7")
        self.gridlayout.addWidget(self.label_7,6,0,1,1)

        self.failed = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.failed.setFont(font)
        self.failed.setObjectName("failed")
        self.gridlayout.addWidget(self.failed,6,2,1,3)

        self.label_8 = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(75)
        font.setBold(True)
        self.label_8.setFont(font)
        self.label_8.setObjectName("label_8")
        self.gridlayout.addWidget(self.label_8,7,0,1,2)

        self.failureRate = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.failureRate.setFont(font)
        self.failureRate.setObjectName("failureRate")
        self.gridlayout.addWidget(self.failureRate,7,2,1,3)

        self.softwareTree = QtGui.QTreeWidget(HostInfo)
        self.softwareTree.setGeometry(QtCore.QRect(200,20,231,211))

        font = QtGui.QFont()
        font.setPointSize(10)
        self.softwareTree.setFont(font)
        self.softwareTree.setEditTriggers(QtGui.QAbstractItemView.NoEditTriggers)
        self.softwareTree.setObjectName("softwareTree")

        self.retranslateUi(HostInfo)
        QtCore.QObject.connect(self.closeButton,QtCore.SIGNAL("pressed()"),HostInfo.close)
        QtCore.QMetaObject.connectSlotsByName(HostInfo)

    def retranslateUi(self, HostInfo):
        HostInfo.setWindowTitle(QtGui.QApplication.translate("HostInfo", "Remote Host Information", None, QtGui.QApplication.UnicodeUTF8))
        self.closeButton.setText(QtGui.QApplication.translate("HostInfo", "Close", None, QtGui.QApplication.UnicodeUTF8))
        self.groupBox.setTitle(QtGui.QApplication.translate("HostInfo", "Host Info", None, QtGui.QApplication.UnicodeUTF8))
        self.label.setText(QtGui.QApplication.translate("HostInfo", "IP:", None, QtGui.QApplication.UnicodeUTF8))
        self.ipAddress.setText(QtGui.QApplication.translate("HostInfo", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.label_2.setText(QtGui.QApplication.translate("HostInfo", "Hostname:", None, QtGui.QApplication.UnicodeUTF8))
        self.hostname.setText(QtGui.QApplication.translate("HostInfo", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.label_3.setText(QtGui.QApplication.translate("HostInfo", "Status:", None, QtGui.QApplication.UnicodeUTF8))
        self.status.setText(QtGui.QApplication.translate("HostInfo", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.label_4.setText(QtGui.QApplication.translate("HostInfo", "OS:", None, QtGui.QApplication.UnicodeUTF8))
        self.os.setText(QtGui.QApplication.translate("HostInfo", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.label_5.setText(QtGui.QApplication.translate("HostInfo", "Architecture:", None, QtGui.QApplication.UnicodeUTF8))
        self.architecture.setText(QtGui.QApplication.translate("HostInfo", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.label_6.setText(QtGui.QApplication.translate("HostInfo", "Rendered:", None, QtGui.QApplication.UnicodeUTF8))
        self.rendered.setText(QtGui.QApplication.translate("HostInfo", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.label_7.setText(QtGui.QApplication.translate("HostInfo", "Failed:", None, QtGui.QApplication.UnicodeUTF8))
        self.failed.setText(QtGui.QApplication.translate("HostInfo", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.label_8.setText(QtGui.QApplication.translate("HostInfo", "Failure Rate:", None, QtGui.QApplication.UnicodeUTF8))
        self.failureRate.setText(QtGui.QApplication.translate("HostInfo", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.softwareTree.headerItem().setText(0,QtGui.QApplication.translate("HostInfo", "Installed Software", None, QtGui.QApplication.UnicodeUTF8))

