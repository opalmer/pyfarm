'''
    This file is part of PyFarm.
    Copyright 2009-2010 (C) Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'QtDesigner/NewTicket.ui'
#
# Created: Fri May 21 17:02:47 2010
#      by: PyQt4 UI code generator 4.7.2
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_SubmitTicket(object):
    def setupUi(self, SubmitTicket):
        SubmitTicket.setObjectName("SubmitTicket")
        SubmitTicket.resize(598, 691)
        self.widget = QtGui.QWidget(SubmitTicket)
        self.widget.setGeometry(QtCore.QRect(10, 0, 581, 681))
        self.widget.setObjectName("widget")
        self.verticalLayout = QtGui.QVBoxLayout(self.widget)
        self.verticalLayout.setObjectName("verticalLayout")
        self.nameEmailGridLayout = QtGui.QGridLayout()
        self.nameEmailGridLayout.setObjectName("nameEmailGridLayout")
        self.contactNameLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setWeight(50)
        font.setBold(False)
        self.contactNameLabel.setFont(font)
        self.contactNameLabel.setObjectName("contactNameLabel")
        self.nameEmailGridLayout.addWidget(self.contactNameLabel, 0, 0, 1, 1)
        self.contactEmailLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setWeight(50)
        font.setBold(False)
        self.contactEmailLabel.setFont(font)
        self.contactEmailLabel.setObjectName("contactEmailLabel")
        self.nameEmailGridLayout.addWidget(self.contactEmailLabel, 0, 1, 1, 1)
        self.contactName = QtGui.QLineEdit(self.widget)
        self.contactName.setObjectName("contactName")
        self.nameEmailGridLayout.addWidget(self.contactName, 1, 0, 1, 1)
        self.contactEmail = QtGui.QLineEdit(self.widget)
        self.contactEmail.setObjectName("contactEmail")
        self.nameEmailGridLayout.addWidget(self.contactEmail, 1, 1, 1, 1)
        self.verticalLayout.addLayout(self.nameEmailGridLayout)
        self.typeSeverityHL = QtGui.QHBoxLayout()
        self.typeSeverityHL.setObjectName("typeSeverityHL")
        self.ticketTypeLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setWeight(50)
        font.setBold(False)
        self.ticketTypeLabel.setFont(font)
        self.ticketTypeLabel.setObjectName("ticketTypeLabel")
        self.typeSeverityHL.addWidget(self.ticketTypeLabel)
        self.ticketType = QtGui.QComboBox(self.widget)
        self.ticketType.setSizeAdjustPolicy(QtGui.QComboBox.AdjustToContents)
        self.ticketType.setObjectName("ticketType")
        self.ticketType.addItem("")
        self.ticketType.addItem("")
        self.typeSeverityHL.addWidget(self.ticketType)
        self.label = QtGui.QLabel(self.widget)
        self.label.setObjectName("label")
        self.typeSeverityHL.addWidget(self.label)
        self.comboBox = QtGui.QComboBox(self.widget)
        self.comboBox.setSizeAdjustPolicy(QtGui.QComboBox.AdjustToContents)
        self.comboBox.setObjectName("comboBox")
        self.comboBox.addItem("")
        self.comboBox.addItem("")
        self.comboBox.addItem("")
        self.typeSeverityHL.addWidget(self.comboBox)
        self.severityLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setWeight(50)
        font.setBold(False)
        self.severityLabel.setFont(font)
        self.severityLabel.setObjectName("severityLabel")
        self.typeSeverityHL.addWidget(self.severityLabel)
        self.severityLevel = QtGui.QComboBox(self.widget)
        self.severityLevel.setSizeAdjustPolicy(QtGui.QComboBox.AdjustToContents)
        self.severityLevel.setObjectName("severityLevel")
        self.severityLevel.addItem("")
        self.severityLevel.addItem("")
        self.severityLevel.addItem("")
        self.severityLevel.addItem("")
        self.severityLevel.addItem("")
        self.typeSeverityHL.addWidget(self.severityLevel)
        self.verticalLayout.addLayout(self.typeSeverityHL)
        self.descriptionHL = QtGui.QVBoxLayout()
        self.descriptionHL.setObjectName("descriptionHL")
        self.descriptionLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setWeight(50)
        font.setBold(False)
        self.descriptionLabel.setFont(font)
        self.descriptionLabel.setObjectName("descriptionLabel")
        self.descriptionHL.addWidget(self.descriptionLabel)
        self.description = QtGui.QTextEdit(self.widget)
        self.description.setObjectName("description")
        self.descriptionHL.addWidget(self.description)
        self.verticalLayout.addLayout(self.descriptionHL)
        self.sysInfoEditHL = QtGui.QHBoxLayout()
        self.sysInfoEditHL.setObjectName("sysInfoEditHL")
        self.sysInfoLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setWeight(50)
        font.setBold(False)
        self.sysInfoLabel.setFont(font)
        self.sysInfoLabel.setObjectName("sysInfoLabel")
        self.sysInfoEditHL.addWidget(self.sysInfoLabel)
        self.sysInfoEdit = QtGui.QPushButton(self.widget)
        self.sysInfoEdit.setObjectName("sysInfoEdit")
        self.sysInfoEditHL.addWidget(self.sysInfoEdit)
        spacerItem = QtGui.QSpacerItem(268, 20, QtGui.QSizePolicy.Expanding, QtGui.QSizePolicy.Minimum)
        self.sysInfoEditHL.addItem(spacerItem)
        self.verticalLayout.addLayout(self.sysInfoEditHL)
        self.sysInfo = QtGui.QTextBrowser(self.widget)
        self.sysInfo.setObjectName("sysInfo")
        self.verticalLayout.addWidget(self.sysInfo)
        self.submitHL = QtGui.QHBoxLayout()
        self.submitHL.setObjectName("submitHL")
        spacerItem1 = QtGui.QSpacerItem(408, 20, QtGui.QSizePolicy.Expanding, QtGui.QSizePolicy.Minimum)
        self.submitHL.addItem(spacerItem1)
        self.submit = QtGui.QPushButton(self.widget)
        self.submit.setObjectName("submit")
        self.submitHL.addWidget(self.submit)
        self.verticalLayout.addLayout(self.submitHL)
        self.requiredFieldLabel = QtGui.QLabel(self.widget)
        self.requiredFieldLabel.setAlignment(QtCore.Qt.AlignRight|QtCore.Qt.AlignTrailing|QtCore.Qt.AlignVCenter)
        self.requiredFieldLabel.setMargin(0)
        self.requiredFieldLabel.setObjectName("requiredFieldLabel")
        self.verticalLayout.addWidget(self.requiredFieldLabel)

        self.retranslateUi(SubmitTicket)
        self.severityLevel.setCurrentIndex(3)
        QtCore.QMetaObject.connectSlotsByName(SubmitTicket)

    def retranslateUi(self, SubmitTicket):
        SubmitTicket.setWindowTitle(QtGui.QApplication.translate("SubmitTicket", "Submit New Ticket", None, QtGui.QApplication.UnicodeUTF8))
        SubmitTicket.setToolTip(QtGui.QApplication.translate("SubmitTicket", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0//EN\" \"http://www.w3.org/TR/REC-html40/strict.dtd\">\n"
"<html><head><meta name=\"qrichtext\" content=\"1\" /><style type=\"text/css\">\n"
"p, li { white-space: pre-wrap; }\n"
"</style></head><body style=\" font-family:\'Sans\'; font-size:10pt; font-weight:400; font-style:normal;\">\n"
"<p style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\"><span style=\" font-weight:600;\">Bug Report</span> -- A Problem needs to be fixed or investigated further.</p>\n"
"<p style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\"><span style=\" font-weight:600;\">Feature Request </span>-- Something you would like to see added to PyFarm.</p></body></html>", None, QtGui.QApplication.UnicodeUTF8))
        self.contactNameLabel.setText(QtGui.QApplication.translate("SubmitTicket", "Name", None, QtGui.QApplication.UnicodeUTF8))
        self.contactEmailLabel.setText(QtGui.QApplication.translate("SubmitTicket", "EMail *", None, QtGui.QApplication.UnicodeUTF8))
        self.ticketTypeLabel.setText(QtGui.QApplication.translate("SubmitTicket", "Type:", None, QtGui.QApplication.UnicodeUTF8))
        self.ticketType.setToolTip(QtGui.QApplication.translate("SubmitTicket", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0//EN\" \"http://www.w3.org/TR/REC-html40/strict.dtd\">\n"
"<html><head><meta name=\"qrichtext\" content=\"1\" /><style type=\"text/css\">\n"
"p, li { white-space: pre-wrap; }\n"
"</style></head><body style=\" font-family:\'Sans\'; font-size:10pt; font-weight:400; font-style:normal;\">\n"
"<p style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\"><span style=\" font-weight:600;\">Bug Report</span> -- A Problem needs to be fixed or investigated further.</p>\n"
"<p style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\"><span style=\" font-weight:600;\">Feature Request </span>-- Something you would like to see added to PyFarm.</p></body></html>", None, QtGui.QApplication.UnicodeUTF8))
        self.ticketType.setItemText(0, QtGui.QApplication.translate("SubmitTicket", "Bug Report", None, QtGui.QApplication.UnicodeUTF8))
        self.ticketType.setItemText(1, QtGui.QApplication.translate("SubmitTicket", "Feature Request", None, QtGui.QApplication.UnicodeUTF8))
        self.label.setText(QtGui.QApplication.translate("SubmitTicket", "Component:", None, QtGui.QApplication.UnicodeUTF8))
        self.comboBox.setItemText(0, QtGui.QApplication.translate("SubmitTicket", "Interface", None, QtGui.QApplication.UnicodeUTF8))
        self.comboBox.setItemText(1, QtGui.QApplication.translate("SubmitTicket", "Remote Client", None, QtGui.QApplication.UnicodeUTF8))
        self.comboBox.setItemText(2, QtGui.QApplication.translate("SubmitTicket", "Corporate", None, QtGui.QApplication.UnicodeUTF8))
        self.severityLabel.setText(QtGui.QApplication.translate("SubmitTicket", "Severity:", None, QtGui.QApplication.UnicodeUTF8))
        self.severityLevel.setItemText(0, QtGui.QApplication.translate("SubmitTicket", "Critical", None, QtGui.QApplication.UnicodeUTF8))
        self.severityLevel.setItemText(1, QtGui.QApplication.translate("SubmitTicket", "High", None, QtGui.QApplication.UnicodeUTF8))
        self.severityLevel.setItemText(2, QtGui.QApplication.translate("SubmitTicket", "Medium", None, QtGui.QApplication.UnicodeUTF8))
        self.severityLevel.setItemText(3, QtGui.QApplication.translate("SubmitTicket", "Low", None, QtGui.QApplication.UnicodeUTF8))
        self.severityLevel.setItemText(4, QtGui.QApplication.translate("SubmitTicket", "Very Low", None, QtGui.QApplication.UnicodeUTF8))
        self.descriptionLabel.setText(QtGui.QApplication.translate("SubmitTicket", "Description", None, QtGui.QApplication.UnicodeUTF8))
        self.description.setToolTip(QtGui.QApplication.translate("SubmitTicket", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0//EN\" \"http://www.w3.org/TR/REC-html40/strict.dtd\">\n"
"<html><head><meta name=\"qrichtext\" content=\"1\" /><style type=\"text/css\">\n"
"p, li { white-space: pre-wrap; }\n"
"</style></head><body style=\" font-family:\'Sans\'; font-size:10pt; font-weight:400; font-style:normal;\">\n"
"<p style=\" margin-top:12px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\">This is where you should tell the developer what you have to say.  If you filing a bug report please use the general guidelines below:</p>\n"
"<ol style=\"-qt-list-indent: 1;\"><li style=\" margin-top:12px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\">Version of PyFarm being used</li>\n"
"<li style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\">Your operating system (Type, version, and architecture)</li>\n"
"<li style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\">The problem you are having</li>\n"
"<li style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\">How have you tried to solve your problem</li>\n"
"<li style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\">Did your troubleshooting change your original problem, if so how?</li>\n"
"<li style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\">Screenshots are always good</li></ol></body></html>", None, QtGui.QApplication.UnicodeUTF8))
        self.sysInfoLabel.setText(QtGui.QApplication.translate("SubmitTicket", "System Information", None, QtGui.QApplication.UnicodeUTF8))
        self.sysInfoEdit.setToolTip(QtGui.QApplication.translate("SubmitTicket", "Edit generated system info", None, QtGui.QApplication.UnicodeUTF8))
        self.sysInfoEdit.setText(QtGui.QApplication.translate("SubmitTicket", "Edit", None, QtGui.QApplication.UnicodeUTF8))
        self.sysInfo.setToolTip(QtGui.QApplication.translate("SubmitTicket", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0//EN\" \"http://www.w3.org/TR/REC-html40/strict.dtd\">\n"
"<html><head><meta name=\"qrichtext\" content=\"1\" /><style type=\"text/css\">\n"
"p, li { white-space: pre-wrap; }\n"
"</style></head><body style=\" font-family:\'Sans\'; font-size:10pt; font-weight:400; font-style:normal;\">\n"
"<p style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\">This is the generated system information.  Use the <span style=\" font-weight:600;\">Edit </span>button to add your own text.</p></body></html>", None, QtGui.QApplication.UnicodeUTF8))
        self.submit.setText(QtGui.QApplication.translate("SubmitTicket", "Submit", None, QtGui.QApplication.UnicodeUTF8))
        self.requiredFieldLabel.setText(QtGui.QApplication.translate("SubmitTicket", "* indicates required fields", None, QtGui.QApplication.UnicodeUTF8))

