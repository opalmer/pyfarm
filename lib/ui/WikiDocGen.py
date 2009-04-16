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

# Form implementation generated from reading ui file 'QtDesigner/WikiDocGen.ui'
#
# Created: Thu Apr 16 17:17:47 2009
#      by: PyQt4 UI code generator 4.3.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_WikiDocGen(object):
    def setupUi(self, WikiDocGen):
        WikiDocGen.setObjectName("WikiDocGen")
        WikiDocGen.resize(QtCore.QSize(QtCore.QRect(0,0,760,555).size()).expandedTo(WikiDocGen.minimumSizeHint()))

        self.layoutWidget = QtGui.QWidget(WikiDocGen)
        self.layoutWidget.setGeometry(QtCore.QRect(10,10,741,541))
        self.layoutWidget.setObjectName("layoutWidget")

        self.gridlayout = QtGui.QGridLayout(self.layoutWidget)
        self.gridlayout.setObjectName("gridlayout")

        self.gridlayout1 = QtGui.QGridLayout()
        self.gridlayout1.setObjectName("gridlayout1")

        self.label = QtGui.QLabel(self.layoutWidget)
        self.label.setObjectName("label")
        self.gridlayout1.addWidget(self.label,0,0,1,1)

        self.startPath = QtGui.QLineEdit(self.layoutWidget)
        self.startPath.setObjectName("startPath")
        self.gridlayout1.addWidget(self.startPath,0,1,1,1)

        self.browseStartPath = QtGui.QPushButton(self.layoutWidget)
        self.browseStartPath.setCursor(QtCore.Qt.PointingHandCursor)
        self.browseStartPath.setObjectName("browseStartPath")
        self.gridlayout1.addWidget(self.browseStartPath,0,2,1,1)
        self.gridlayout.addLayout(self.gridlayout1,0,0,1,1)

        self.gridlayout2 = QtGui.QGridLayout()
        self.gridlayout2.setObjectName("gridlayout2")

        self.label_2 = QtGui.QLabel(self.layoutWidget)
        self.label_2.setObjectName("label_2")
        self.gridlayout2.addWidget(self.label_2,0,0,1,1)

        self.outPath = QtGui.QLineEdit(self.layoutWidget)
        self.outPath.setObjectName("outPath")
        self.gridlayout2.addWidget(self.outPath,0,1,1,1)

        self.browseOutPath = QtGui.QPushButton(self.layoutWidget)
        self.browseOutPath.setCursor(QtCore.Qt.PointingHandCursor)
        self.browseOutPath.setObjectName("browseOutPath")
        self.gridlayout2.addWidget(self.browseOutPath,0,2,1,1)
        self.gridlayout.addLayout(self.gridlayout2,1,0,1,1)

        self.label_3 = QtGui.QLabel(self.layoutWidget)
        self.label_3.setObjectName("label_3")
        self.gridlayout.addWidget(self.label_3,2,0,1,1)

        self.selectedFiles = QtGui.QListWidget(self.layoutWidget)
        self.selectedFiles.setSelectionMode(QtGui.QAbstractItemView.ExtendedSelection)
        self.selectedFiles.setObjectName("selectedFiles")
        self.gridlayout.addWidget(self.selectedFiles,3,0,1,1)

        self.progress = QtGui.QProgressBar(self.layoutWidget)
        self.progress.setProperty("value",QtCore.QVariant(0))
        self.progress.setObjectName("progress")
        self.gridlayout.addWidget(self.progress,4,0,1,1)

        self.hboxlayout = QtGui.QHBoxLayout()
        self.hboxlayout.setObjectName("hboxlayout")

        self.quit = QtGui.QPushButton(self.layoutWidget)
        self.quit.setObjectName("quit")
        self.hboxlayout.addWidget(self.quit)

        spacerItem = QtGui.QSpacerItem(40,20,QtGui.QSizePolicy.Expanding,QtGui.QSizePolicy.Minimum)
        self.hboxlayout.addItem(spacerItem)

        self.makeDocs = QtGui.QPushButton(self.layoutWidget)
        self.makeDocs.setObjectName("makeDocs")
        self.hboxlayout.addWidget(self.makeDocs)
        self.gridlayout.addLayout(self.hboxlayout,5,0,1,1)

        self.retranslateUi(WikiDocGen)
        QtCore.QObject.connect(self.quit,QtCore.SIGNAL("pressed()"),WikiDocGen.close)
        QtCore.QMetaObject.connectSlotsByName(WikiDocGen)
        WikiDocGen.setTabOrder(self.startPath,self.browseStartPath)
        WikiDocGen.setTabOrder(self.browseStartPath,self.outPath)
        WikiDocGen.setTabOrder(self.outPath,self.browseOutPath)
        WikiDocGen.setTabOrder(self.browseOutPath,self.selectedFiles)
        WikiDocGen.setTabOrder(self.selectedFiles,self.makeDocs)
        WikiDocGen.setTabOrder(self.makeDocs,self.quit)

    def retranslateUi(self, WikiDocGen):
        WikiDocGen.setWindowTitle(QtGui.QApplication.translate("WikiDocGen", "PyFarm Wiki Doc Generator", None, QtGui.QApplication.UnicodeUTF8))
        self.label.setText(QtGui.QApplication.translate("WikiDocGen", "Start Path:", None, QtGui.QApplication.UnicodeUTF8))
        self.browseStartPath.setText(QtGui.QApplication.translate("WikiDocGen", "Browse", None, QtGui.QApplication.UnicodeUTF8))
        self.label_2.setText(QtGui.QApplication.translate("WikiDocGen", "Out Path:", None, QtGui.QApplication.UnicodeUTF8))
        self.browseOutPath.setText(QtGui.QApplication.translate("WikiDocGen", "Browse", None, QtGui.QApplication.UnicodeUTF8))
        self.label_3.setText(QtGui.QApplication.translate("WikiDocGen", "Files:", None, QtGui.QApplication.UnicodeUTF8))
        self.quit.setText(QtGui.QApplication.translate("WikiDocGen", "Quit", None, QtGui.QApplication.UnicodeUTF8))
        self.makeDocs.setText(QtGui.QApplication.translate("WikiDocGen", "Make Docs", None, QtGui.QApplication.UnicodeUTF8))

