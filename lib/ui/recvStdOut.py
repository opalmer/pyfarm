# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'recvStdOut.ui'
#
# Created: Wed Jan 28 13:04:45 2009
#      by: PyQt4 UI code generator 4.4.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_StdOutputServer(object):
    def setupUi(self, StdOutputServer):
        StdOutputServer.setObjectName("StdOutputServer")
        StdOutputServer.resize(1144, 741)
        self.stdOutput = QtGui.QTextBrowser(StdOutputServer)
        self.stdOutput.setGeometry(QtCore.QRect(10, 30, 1111, 701))
        self.stdOutput.setObjectName("stdOutput")
        self.label = QtGui.QLabel(StdOutputServer)
        self.label.setGeometry(QtCore.QRect(10, 10, 131, 18))
        self.label.setObjectName("label")

        self.retranslateUi(StdOutputServer)
        QtCore.QMetaObject.connectSlotsByName(StdOutputServer)

    def retranslateUi(self, StdOutputServer):
        StdOutputServer.setWindowTitle(QtGui.QApplication.translate("StdOutputServer", "Form", None, QtGui.QApplication.UnicodeUTF8))
        self.label.setText(QtGui.QApplication.translate("StdOutputServer", "Output From Server:", None, QtGui.QApplication.UnicodeUTF8))
