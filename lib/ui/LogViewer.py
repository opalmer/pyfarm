# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'QtDesigner/LogViewer.ui'
#
# Created: Wed Mar 11 00:27:41 2009
#      by: PyQt4 UI code generator 4.4.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_LogViewer(object):
    def setupUi(self, LogViewer):
        LogViewer.setObjectName("LogViewer")
        LogViewer.resize(602, 709)
        self.groupBox = QtGui.QGroupBox(LogViewer)
        self.groupBox.setGeometry(QtCore.QRect(20, 10, 561, 651))
        font = QtGui.QFont()
        font.setPointSize(12)
        self.groupBox.setFont(font)
        self.groupBox.setObjectName("groupBox")
        self.log = QtGui.QTextBrowser(self.groupBox)
        self.log.setGeometry(QtCore.QRect(10, 30, 541, 611))
        font = QtGui.QFont()
        font.setPointSize(10)
        self.log.setFont(font)
        self.log.setObjectName("log")
        self.closeLogViewer = QtGui.QPushButton(LogViewer)
        self.closeLogViewer.setGeometry(QtCore.QRect(500, 670, 80, 28))
        self.closeLogViewer.setObjectName("closeLogViewer")

        self.retranslateUi(LogViewer)
        QtCore.QObject.connect(self.closeLogViewer, QtCore.SIGNAL("pressed()"), LogViewer.close)
        QtCore.QMetaObject.connectSlotsByName(LogViewer)

    def retranslateUi(self, LogViewer):
        LogViewer.setWindowTitle(QtGui.QApplication.translate("LogViewer", "Render Log View", None, QtGui.QApplication.UnicodeUTF8))
        self.groupBox.setTitle(QtGui.QApplication.translate("LogViewer", "Frame Log", None, QtGui.QApplication.UnicodeUTF8))
        self.closeLogViewer.setText(QtGui.QApplication.translate("LogViewer", "Close", None, QtGui.QApplication.UnicodeUTF8))

