# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'QtDesigner/AddCustomHost.ui'
#
# Created: Sat Jan 24 15:31:08 2009
#      by: PyQt4 UI code generator 4.4.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4.QtCore import *
from PyQt4.QtGui import *

class Ui_AddCustomHost(QDialog):
    def __init__(self, parent=None):
        super(Ui_AddCustomHost, self).__init__(parent)
        self.setupUi(self)

    def setupUi(self, AddCustomHost):
        AddCustomHost.setObjectName("AddCustomHost")
        AddCustomHost.resize(329, 113)
        self.widget = QWidget(AddCustomHost)
        self.widget.setGeometry(QRect(20, 24, 281, 70))
        self.widget.setObjectName("widget")
        self.verticalLayout = QVBoxLayout(self.widget)
        self.verticalLayout.setObjectName("verticalLayout")
        self.horizontalLayout = QHBoxLayout()
        self.horizontalLayout.setObjectName("horizontalLayout")
        self.hostLabel = QLabel(self.widget)
        font = QFont()
        font.setPointSize(10)
        self.hostLabel.setFont(font)
        self.hostLabel.setObjectName("hostLabel")
        self.horizontalLayout.addWidget(self.hostLabel)
        self.inputHost = QLineEdit(self.widget)
        self.inputHost.setObjectName("inputHost")
        self.horizontalLayout.addWidget(self.inputHost)
        self.verticalLayout.addLayout(self.horizontalLayout)
        self.buttons = QDialogButtonBox(self.widget)
        self.buttons.setStandardButtons(QDialogButtonBox.Cancel|QDialogButtonBox.Ok)
        self.buttons.setObjectName("buttons")
        self.verticalLayout.addWidget(self.buttons)

        self.retranslateUi(AddCustomHost)
        QObject.connect(self.buttons, SIGNAL("rejected()"), AddCustomHost.close)
        QMetaObject.connectSlotsByName(AddCustomHost)


    def retranslateUi(self, AddCustomHost):
        AddCustomHost.setWindowTitle(QApplication.translate("AddCustomHost", "Form", None, QApplication.UnicodeUTF8))
        self.hostLabel.setText(QApplication.translate("AddCustomHost", "Host:", None, QApplication.UnicodeUTF8))
