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

# Form implementation generated from reading ui file 'QtDesigner/JobDetails.ui'
#
# Created: Sun Apr 12 17:23:06 2009
#      by: PyQt4 UI code generator 4.4.2
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_JobDetails(object):
    def setupUi(self, JobDetails):
        JobDetails.setObjectName("JobDetails")
        JobDetails.resize(736,674)
        self.framesGroupBox = QtGui.QGroupBox(JobDetails)
        self.framesGroupBox.setGeometry(QtCore.QRect(140,10,581,601))
        font = QtGui.QFont()
        font.setPointSize(12)
        self.framesGroupBox.setFont(font)
        self.framesGroupBox.setObjectName("framesGroupBox")
        self.frameTable = QtGui.QTableWidget(self.framesGroupBox)
        self.frameTable.setGeometry(QtCore.QRect(10,20,561,571))
        font = QtGui.QFont()
        font.setPointSize(10)
        self.frameTable.setFont(font)
        self.frameTable.setEditTriggers(QtGui.QAbstractItemView.NoEditTriggers)
        self.frameTable.setSelectionBehavior(QtGui.QAbstractItemView.SelectRows)
        self.frameTable.setObjectName("frameTable")
        self.closeButton = QtGui.QPushButton(JobDetails)
        self.closeButton.setGeometry(QtCore.QRect(640,630,80,28))
        self.closeButton.setObjectName("closeButton")
        self.jobGroupBox = QtGui.QGroupBox(JobDetails)
        self.jobGroupBox.setGeometry(QtCore.QRect(10,10,120,81))
        font = QtGui.QFont()
        font.setPointSize(12)
        self.jobGroupBox.setFont(font)
        self.jobGroupBox.setObjectName("jobGroupBox")
        self.layoutWidget = QtGui.QWidget(self.jobGroupBox)
        self.layoutWidget.setGeometry(QtCore.QRect(10,23,111,51))
        self.layoutWidget.setObjectName("layoutWidget")
        self.gridLayout = QtGui.QGridLayout(self.layoutWidget)
        self.gridLayout.setObjectName("gridLayout")
        self.jobDetails_job_status_label = QtGui.QLabel(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobDetails_job_status_label.setFont(font)
        self.jobDetails_job_status_label.setObjectName("jobDetails_job_status_label")
        self.gridLayout.addWidget(self.jobDetails_job_status_label,0,0,1,1)
        self.jobDetails_job_name = QtGui.QLabel(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobDetails_job_name.setFont(font)
        self.jobDetails_job_name.setObjectName("jobDetails_job_name")
        self.gridLayout.addWidget(self.jobDetails_job_name,0,1,1,1)
        self.jobDetails_job_name_label = QtGui.QLabel(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobDetails_job_name_label.setFont(font)
        self.jobDetails_job_name_label.setObjectName("jobDetails_job_name_label")
        self.gridLayout.addWidget(self.jobDetails_job_name_label,1,0,1,1)
        self.jobDetails_job_status = QtGui.QLabel(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobDetails_job_status.setFont(font)
        self.jobDetails_job_status.setObjectName("jobDetails_job_status")
        self.gridLayout.addWidget(self.jobDetails_job_status,1,1,1,1)

        self.retranslateUi(JobDetails)
        QtCore.QObject.connect(self.closeButton,QtCore.SIGNAL("pressed()"),JobDetails.close)
        QtCore.QMetaObject.connectSlotsByName(JobDetails)

    def retranslateUi(self, JobDetails):
        JobDetails.setWindowTitle(QtGui.QApplication.translate("JobDetails", "Job Details", None, QtGui.QApplication.UnicodeUTF8))
        self.framesGroupBox.setTitle(QtGui.QApplication.translate("JobDetails", "Frames", None, QtGui.QApplication.UnicodeUTF8))
        self.frameTable.clear()
        self.frameTable.setColumnCount(4)
        self.frameTable.setRowCount(0)
        headerItem = QtGui.QTableWidgetItem()
        headerItem.setText(QtGui.QApplication.translate("JobDetails", "Status", None, QtGui.QApplication.UnicodeUTF8))
        self.frameTable.setHorizontalHeaderItem(0,headerItem)
        headerItem1 = QtGui.QTableWidgetItem()
        headerItem1.setText(QtGui.QApplication.translate("JobDetails", "Group ID", None, QtGui.QApplication.UnicodeUTF8))
        self.frameTable.setHorizontalHeaderItem(1,headerItem1)
        headerItem2 = QtGui.QTableWidgetItem()
        headerItem2.setText(QtGui.QApplication.translate("JobDetails", "Progress", None, QtGui.QApplication.UnicodeUTF8))
        self.frameTable.setHorizontalHeaderItem(2,headerItem2)
        headerItem3 = QtGui.QTableWidgetItem()
        headerItem3.setText(QtGui.QApplication.translate("JobDetails", "Log", None, QtGui.QApplication.UnicodeUTF8))
        self.frameTable.setHorizontalHeaderItem(3,headerItem3)
        self.closeButton.setText(QtGui.QApplication.translate("JobDetails", "Close", None, QtGui.QApplication.UnicodeUTF8))
        self.jobGroupBox.setTitle(QtGui.QApplication.translate("JobDetails", "Job", None, QtGui.QApplication.UnicodeUTF8))
        self.jobDetails_job_status_label.setText(QtGui.QApplication.translate("JobDetails", "Name:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobDetails_job_name.setText(QtGui.QApplication.translate("JobDetails", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.jobDetails_job_name_label.setText(QtGui.QApplication.translate("JobDetails", "Status:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobDetails_job_status.setText(QtGui.QApplication.translate("JobDetails", "NONE", None, QtGui.QApplication.UnicodeUTF8))

