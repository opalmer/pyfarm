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
# Created: Thu Apr 30 13:33:56 2009
#      by: PyQt4 UI code generator 4.3.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_JobDetails(object):
    def setupUi(self, JobDetails):
        JobDetails.setObjectName("JobDetails")
        JobDetails.resize(QtCore.QSize(QtCore.QRect(0,0,837,666).size()).expandedTo(JobDetails.minimumSizeHint()))

        self.jobGroupBox = QtGui.QGroupBox(JobDetails)
        self.jobGroupBox.setGeometry(QtCore.QRect(10,200,120,81))
        self.jobGroupBox.setObjectName("jobGroupBox")

        self.layoutWidget = QtGui.QWidget(self.jobGroupBox)
        self.layoutWidget.setGeometry(QtCore.QRect(10,30,111,48))
        self.layoutWidget.setObjectName("layoutWidget")

        self.gridlayout = QtGui.QGridLayout(self.layoutWidget)
        self.gridlayout.setObjectName("gridlayout")

        self.jobDetails_job_status_label = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobDetails_job_status_label.setFont(font)
        self.jobDetails_job_status_label.setObjectName("jobDetails_job_status_label")
        self.gridlayout.addWidget(self.jobDetails_job_status_label,0,0,1,1)

        self.jobDetails_job_name = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobDetails_job_name.setFont(font)
        self.jobDetails_job_name.setObjectName("jobDetails_job_name")
        self.gridlayout.addWidget(self.jobDetails_job_name,0,1,1,1)

        self.jobDetails_job_name_label = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobDetails_job_name_label.setFont(font)
        self.jobDetails_job_name_label.setObjectName("jobDetails_job_name_label")
        self.gridlayout.addWidget(self.jobDetails_job_name_label,1,0,1,1)

        self.jobDetails_job_status = QtGui.QLabel(self.layoutWidget)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobDetails_job_status.setFont(font)
        self.jobDetails_job_status.setObjectName("jobDetails_job_status")
        self.gridlayout.addWidget(self.jobDetails_job_status,1,1,1,1)

        self.groupBox_2 = QtGui.QGroupBox(JobDetails)
        self.groupBox_2.setGeometry(QtCore.QRect(10,50,121,141))
        self.groupBox_2.setObjectName("groupBox_2")

        self.layoutWidget_2 = QtGui.QWidget(self.groupBox_2)
        self.layoutWidget_2.setGeometry(QtCore.QRect(9,20,101,101))
        self.layoutWidget_2.setObjectName("layoutWidget_2")

        self.gridlayout1 = QtGui.QGridLayout(self.layoutWidget_2)
        self.gridlayout1.setObjectName("gridlayout1")

        self.label_44 = QtGui.QLabel(self.layoutWidget_2)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.label_44.setFont(font)
        self.label_44.setObjectName("label_44")
        self.gridlayout1.addWidget(self.label_44,0,0,1,1)

        self.jobs_frames_waiting = QtGui.QLabel(self.layoutWidget_2)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobs_frames_waiting.setFont(font)
        self.jobs_frames_waiting.setObjectName("jobs_frames_waiting")
        self.gridlayout1.addWidget(self.jobs_frames_waiting,0,1,1,1)

        self.label_28 = QtGui.QLabel(self.layoutWidget_2)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.label_28.setFont(font)
        self.label_28.setObjectName("label_28")
        self.gridlayout1.addWidget(self.label_28,1,0,1,1)

        self.jobs_frames_rendering = QtGui.QLabel(self.layoutWidget_2)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobs_frames_rendering.setFont(font)
        self.jobs_frames_rendering.setObjectName("jobs_frames_rendering")
        self.gridlayout1.addWidget(self.jobs_frames_rendering,1,1,1,1)

        self.label_31 = QtGui.QLabel(self.layoutWidget_2)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.label_31.setFont(font)
        self.label_31.setObjectName("label_31")
        self.gridlayout1.addWidget(self.label_31,2,0,1,1)

        self.jobs_frames_complete = QtGui.QLabel(self.layoutWidget_2)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobs_frames_complete.setFont(font)
        self.jobs_frames_complete.setObjectName("jobs_frames_complete")
        self.gridlayout1.addWidget(self.jobs_frames_complete,2,1,1,1)

        self.label_32 = QtGui.QLabel(self.layoutWidget_2)

        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.label_32.setFont(font)
        self.label_32.setObjectName("label_32")
        self.gridlayout1.addWidget(self.label_32,3,0,1,1)

        self.jobs_frames_failed = QtGui.QLabel(self.layoutWidget_2)

        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobs_frames_failed.setFont(font)
        self.jobs_frames_failed.setObjectName("jobs_frames_failed")
        self.gridlayout1.addWidget(self.jobs_frames_failed,3,1,1,1)

        self.layoutWidget1 = QtGui.QWidget(JobDetails)
        self.layoutWidget1.setGeometry(QtCore.QRect(160,11,661,641))
        self.layoutWidget1.setObjectName("layoutWidget1")

        self.vboxlayout = QtGui.QVBoxLayout(self.layoutWidget1)
        self.vboxlayout.setObjectName("vboxlayout")

        self.label = QtGui.QLabel(self.layoutWidget1)

        font = QtGui.QFont()
        font.setFamily("AlArabiya")
        font.setPointSize(14)
        font.setWeight(75)
        font.setBold(True)
        self.label.setFont(font)
        self.label.setAlignment(QtCore.Qt.AlignCenter)
        self.label.setObjectName("label")
        self.vboxlayout.addWidget(self.label)

        self.frameTable = QtGui.QTableView(self.layoutWidget1)
        self.frameTable.setEditTriggers(QtGui.QAbstractItemView.NoEditTriggers)
        self.frameTable.setSelectionBehavior(QtGui.QAbstractItemView.SelectRows)
        self.frameTable.setObjectName("frameTable")
        self.vboxlayout.addWidget(self.frameTable)

        self.hboxlayout = QtGui.QHBoxLayout()
        self.hboxlayout.setObjectName("hboxlayout")

        self.refreshNow = QtGui.QPushButton(self.layoutWidget1)
        self.refreshNow.setObjectName("refreshNow")
        self.hboxlayout.addWidget(self.refreshNow)

        self.autoRefresh = QtGui.QCheckBox(self.layoutWidget1)
        self.autoRefresh.setObjectName("autoRefresh")
        self.hboxlayout.addWidget(self.autoRefresh)

        self.refreshTime = QtGui.QSpinBox(self.layoutWidget1)
        self.refreshTime.setEnabled(False)
        self.refreshTime.setMinimum(1)
        self.refreshTime.setMaximum(600)
        self.refreshTime.setProperty("value",QtCore.QVariant(5))
        self.refreshTime.setObjectName("refreshTime")
        self.hboxlayout.addWidget(self.refreshTime)

        spacerItem = QtGui.QSpacerItem(188,20,QtGui.QSizePolicy.Expanding,QtGui.QSizePolicy.Minimum)
        self.hboxlayout.addItem(spacerItem)

        self.saveFrameLog = QtGui.QPushButton(self.layoutWidget1)
        self.saveFrameLog.setObjectName("saveFrameLog")
        self.hboxlayout.addWidget(self.saveFrameLog)

        self.closeButton = QtGui.QPushButton(self.layoutWidget1)
        self.closeButton.setObjectName("closeButton")
        self.hboxlayout.addWidget(self.closeButton)
        self.vboxlayout.addLayout(self.hboxlayout)

        self.retranslateUi(JobDetails)
        QtCore.QObject.connect(self.closeButton,QtCore.SIGNAL("pressed()"),JobDetails.close)
        QtCore.QMetaObject.connectSlotsByName(JobDetails)

    def retranslateUi(self, JobDetails):
        JobDetails.setWindowTitle(QtGui.QApplication.translate("JobDetails", "Job Details", None, QtGui.QApplication.UnicodeUTF8))
        self.jobGroupBox.setTitle(QtGui.QApplication.translate("JobDetails", "Job", None, QtGui.QApplication.UnicodeUTF8))
        self.jobDetails_job_status_label.setText(QtGui.QApplication.translate("JobDetails", "Name:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobDetails_job_name.setText(QtGui.QApplication.translate("JobDetails", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.jobDetails_job_name_label.setText(QtGui.QApplication.translate("JobDetails", "Status:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobDetails_job_status.setText(QtGui.QApplication.translate("JobDetails", "NONE", None, QtGui.QApplication.UnicodeUTF8))
        self.groupBox_2.setTitle(QtGui.QApplication.translate("JobDetails", "Stats", None, QtGui.QApplication.UnicodeUTF8))
        self.label_44.setText(QtGui.QApplication.translate("JobDetails", "Waiting:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobs_frames_waiting.setText(QtGui.QApplication.translate("JobDetails", "0", None, QtGui.QApplication.UnicodeUTF8))
        self.label_28.setText(QtGui.QApplication.translate("JobDetails", "Rendering:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobs_frames_rendering.setText(QtGui.QApplication.translate("JobDetails", "0", None, QtGui.QApplication.UnicodeUTF8))
        self.label_31.setText(QtGui.QApplication.translate("JobDetails", "Complete:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobs_frames_complete.setText(QtGui.QApplication.translate("JobDetails", "0", None, QtGui.QApplication.UnicodeUTF8))
        self.label_32.setText(QtGui.QApplication.translate("JobDetails", "Failed:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobs_frames_failed.setText(QtGui.QApplication.translate("JobDetails", "0", None, QtGui.QApplication.UnicodeUTF8))
        self.label.setText(QtGui.QApplication.translate("JobDetails", "Frame Log", None, QtGui.QApplication.UnicodeUTF8))
        self.refreshNow.setText(QtGui.QApplication.translate("JobDetails", "Refresh", None, QtGui.QApplication.UnicodeUTF8))
        self.autoRefresh.setText(QtGui.QApplication.translate("JobDetails", "Auto Refresh", None, QtGui.QApplication.UnicodeUTF8))
        self.refreshTime.setSuffix(QtGui.QApplication.translate("JobDetails", "s", None, QtGui.QApplication.UnicodeUTF8))
        self.saveFrameLog.setText(QtGui.QApplication.translate("JobDetails", "Save Log", None, QtGui.QApplication.UnicodeUTF8))
        self.closeButton.setText(QtGui.QApplication.translate("JobDetails", "Close", None, QtGui.QApplication.UnicodeUTF8))

