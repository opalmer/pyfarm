# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'QtDesigner/QProcessTesting.ui'
#
# Created: Mon Jan 26 22:26:21 2009
#      by: PyQt4 UI code generator 4.4.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_QProcessTest(object):
    def setupUi(self, QProcessTest):
        QProcessTest.setObjectName("QProcessTest")
        QProcessTest.resize(1271, 896)
        self.widget = QtGui.QWidget(QProcessTest)
        self.widget.setGeometry(QtCore.QRect(10, 12, 1241, 871))
        self.widget.setObjectName("widget")
        self.verticalLayout_3 = QtGui.QVBoxLayout(self.widget)
        self.verticalLayout_3.setSizeConstraint(QtGui.QLayout.SetMinAndMaxSize)
        self.verticalLayout_3.setContentsMargins(0, -1, -1, -1)
        self.verticalLayout_3.setObjectName("verticalLayout_3")
        self.horizontalLayout_2 = QtGui.QHBoxLayout()
        self.horizontalLayout_2.setObjectName("horizontalLayout_2")
        self.verticalLayout_2 = QtGui.QVBoxLayout()
        self.verticalLayout_2.setObjectName("verticalLayout_2")
        self.label_3 = QtGui.QLabel(self.widget)
        self.label_3.setObjectName("label_3")
        self.verticalLayout_2.addWidget(self.label_3)
        self.mainOut = QtGui.QTextBrowser(self.widget)
        self.mainOut.setObjectName("mainOut")
        self.verticalLayout_2.addWidget(self.mainOut)
        self.horizontalLayout_2.addLayout(self.verticalLayout_2)
        self.verticalLayout = QtGui.QVBoxLayout()
        self.verticalLayout.setObjectName("verticalLayout")
        self.label_5 = QtGui.QLabel(self.widget)
        self.label_5.setObjectName("label_5")
        self.verticalLayout.addWidget(self.label_5)
        self.generalOut = QtGui.QTextBrowser(self.widget)
        self.generalOut.setObjectName("generalOut")
        self.verticalLayout.addWidget(self.generalOut)
        self.horizontalLayout_2.addLayout(self.verticalLayout)
        self.verticalLayout_3.addLayout(self.horizontalLayout_2)
        self.horizontalLayout = QtGui.QHBoxLayout()
        self.horizontalLayout.setObjectName("horizontalLayout")
        self.label_2 = QtGui.QLabel(self.widget)
        self.label_2.setObjectName("label_2")
        self.horizontalLayout.addWidget(self.label_2)
        self.command = QtGui.QLineEdit(self.widget)
        self.command.setObjectName("command")
        self.horizontalLayout.addWidget(self.command)
        self.label = QtGui.QLabel(self.widget)
        self.label.setObjectName("label")
        self.horizontalLayout.addWidget(self.label)
        self.arguments = QtGui.QLineEdit(self.widget)
        self.arguments.setObjectName("arguments")
        self.horizontalLayout.addWidget(self.arguments)
        self.startButton = QtGui.QPushButton(self.widget)
        self.startButton.setObjectName("startButton")
        self.horizontalLayout.addWidget(self.startButton)
        self.stopButton = QtGui.QPushButton(self.widget)
        self.stopButton.setEnabled(False)
        self.stopButton.setObjectName("stopButton")
        self.horizontalLayout.addWidget(self.stopButton)
        self.verticalLayout_3.addLayout(self.horizontalLayout)

        self.retranslateUi(QProcessTest)
        QtCore.QMetaObject.connectSlotsByName(QProcessTest)
        QProcessTest.setTabOrder(self.command, self.arguments)
        QProcessTest.setTabOrder(self.arguments, self.startButton)
        QProcessTest.setTabOrder(self.startButton, self.stopButton)
        QProcessTest.setTabOrder(self.stopButton, self.mainOut)
        QProcessTest.setTabOrder(self.mainOut, self.generalOut)

    def retranslateUi(self, QProcessTest):
        QProcessTest.setWindowTitle(QtGui.QApplication.translate("QProcessTest", "QProcess Test Widget", None, QtGui.QApplication.UnicodeUTF8))
        self.label_3.setText(QtGui.QApplication.translate("QProcessTest", "Main Output", None, QtGui.QApplication.UnicodeUTF8))
        self.label_5.setText(QtGui.QApplication.translate("QProcessTest", "General Output", None, QtGui.QApplication.UnicodeUTF8))
        self.label_2.setText(QtGui.QApplication.translate("QProcessTest", "Command:", None, QtGui.QApplication.UnicodeUTF8))
        self.label.setText(QtGui.QApplication.translate("QProcessTest", "Arguments:", None, QtGui.QApplication.UnicodeUTF8))
        self.startButton.setText(QtGui.QApplication.translate("QProcessTest", "&Start", None, QtGui.QApplication.UnicodeUTF8))
        self.stopButton.setText(QtGui.QApplication.translate("QProcessTest", "Sto&p", None, QtGui.QApplication.UnicodeUTF8))

