'''
    This file is part of PyFarm.
    Copyright 2008-2010 (C) Oliver Palmer

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

# Form implementation generated from reading ui file 'QtDesigner/JobDetails.ui'
#
# Created: Sat May 22 18:21:24 2010
#      by: PyQt4 UI code generator 4.7.2
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_JobDetails(object):
    def setupUi(self, JobDetails):
        JobDetails.setObjectName("JobDetails")
        JobDetails.resize(800, 700)
        self.centralwidget = QtGui.QWidget(JobDetails)
        self.centralwidget.setObjectName("centralwidget")
        self.frameTable = QtGui.QTableView(self.centralwidget)
        self.frameTable.setGeometry(QtCore.QRect(120, 50, 671, 551))
        self.frameTable.setEditTriggers(QtGui.QAbstractItemView.NoEditTriggers)
        self.frameTable.setSelectionMode(QtGui.QAbstractItemView.SingleSelection)
        self.frameTable.setSelectionBehavior(QtGui.QAbstractItemView.SelectRows)
        self.frameTable.setObjectName("frameTable")
        self.stats = QtGui.QGroupBox(self.centralwidget)
        self.stats.setEnabled(False)
        self.stats.setGeometry(QtCore.QRect(10, 30, 121, 141))
        self.stats.setObjectName("stats")
        self.layoutWidget_2 = QtGui.QWidget(self.stats)
        self.layoutWidget_2.setGeometry(QtCore.QRect(9, 20, 101, 101))
        self.layoutWidget_2.setObjectName("layoutWidget_2")
        self.gridlayout = QtGui.QGridLayout(self.layoutWidget_2)
        self.gridlayout.setObjectName("gridlayout")
        self.label_44 = QtGui.QLabel(self.layoutWidget_2)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.label_44.setFont(font)
        self.label_44.setObjectName("label_44")
        self.gridlayout.addWidget(self.label_44, 0, 0, 1, 1)
        self.jobs_frames_waiting = QtGui.QLabel(self.layoutWidget_2)
        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobs_frames_waiting.setFont(font)
        self.jobs_frames_waiting.setObjectName("jobs_frames_waiting")
        self.gridlayout.addWidget(self.jobs_frames_waiting, 0, 1, 1, 1)
        self.label_28 = QtGui.QLabel(self.layoutWidget_2)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.label_28.setFont(font)
        self.label_28.setObjectName("label_28")
        self.gridlayout.addWidget(self.label_28, 1, 0, 1, 1)
        self.jobs_frames_rendering = QtGui.QLabel(self.layoutWidget_2)
        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobs_frames_rendering.setFont(font)
        self.jobs_frames_rendering.setObjectName("jobs_frames_rendering")
        self.gridlayout.addWidget(self.jobs_frames_rendering, 1, 1, 1, 1)
        self.label_31 = QtGui.QLabel(self.layoutWidget_2)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.label_31.setFont(font)
        self.label_31.setObjectName("label_31")
        self.gridlayout.addWidget(self.label_31, 2, 0, 1, 1)
        self.jobs_frames_complete = QtGui.QLabel(self.layoutWidget_2)
        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobs_frames_complete.setFont(font)
        self.jobs_frames_complete.setObjectName("jobs_frames_complete")
        self.gridlayout.addWidget(self.jobs_frames_complete, 2, 1, 1, 1)
        self.label_32 = QtGui.QLabel(self.layoutWidget_2)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.label_32.setFont(font)
        self.label_32.setObjectName("label_32")
        self.gridlayout.addWidget(self.label_32, 3, 0, 1, 1)
        self.jobs_frames_failed = QtGui.QLabel(self.layoutWidget_2)
        font = QtGui.QFont()
        font.setPointSize(10)
        self.jobs_frames_failed.setFont(font)
        self.jobs_frames_failed.setObjectName("jobs_frames_failed")
        self.gridlayout.addWidget(self.jobs_frames_failed, 3, 1, 1, 1)
        self.header = QtGui.QLabel(self.centralwidget)
        self.header.setGeometry(QtCore.QRect(120, 12, 671, 35))
        font = QtGui.QFont()
        font.setPointSize(12)
        font.setWeight(75)
        font.setBold(True)
        self.header.setFont(font)
        self.header.setAlignment(QtCore.Qt.AlignCenter)
        self.header.setObjectName("header")
        self.refreshNow = QtGui.QPushButton(self.centralwidget)
        self.refreshNow.setGeometry(QtCore.QRect(123, 609, 85, 27))
        self.refreshNow.setObjectName("refreshNow")
        self.openLog = QtGui.QPushButton(self.centralwidget)
        self.openLog.setGeometry(QtCore.QRect(606, 609, 85, 27))
        self.openLog.setObjectName("openLog")
        self.autoRefresh = QtGui.QCheckBox(self.centralwidget)
        self.autoRefresh.setGeometry(QtCore.QRect(214, 611, 111, 23))
        self.autoRefresh.setObjectName("autoRefresh")
        self.refreshTime = QtGui.QSpinBox(self.centralwidget)
        self.refreshTime.setEnabled(False)
        self.refreshTime.setGeometry(QtCore.QRect(331, 608, 58, 26))
        self.refreshTime.setMinimum(1)
        self.refreshTime.setMaximum(600)
        self.refreshTime.setProperty("value", 5)
        self.refreshTime.setObjectName("refreshTime")
        self.closeButton = QtGui.QPushButton(self.centralwidget)
        self.closeButton.setGeometry(QtCore.QRect(697, 609, 85, 27))
        self.closeButton.setObjectName("closeButton")
        JobDetails.setCentralWidget(self.centralwidget)
        self.menubar = QtGui.QMenuBar(JobDetails)
        self.menubar.setGeometry(QtCore.QRect(0, 0, 800, 27))
        self.menubar.setObjectName("menubar")
        JobDetails.setMenuBar(self.menubar)
        self.statusbar = QtGui.QStatusBar(JobDetails)
        self.statusbar.setObjectName("statusbar")
        JobDetails.setStatusBar(self.statusbar)

        self.retranslateUi(JobDetails)
        QtCore.QObject.connect(self.closeButton, QtCore.SIGNAL("clicked()"), JobDetails.close)
        QtCore.QMetaObject.connectSlotsByName(JobDetails)
        JobDetails.setTabOrder(self.frameTable, self.refreshNow)
        JobDetails.setTabOrder(self.refreshNow, self.autoRefresh)
        JobDetails.setTabOrder(self.autoRefresh, self.refreshTime)
        JobDetails.setTabOrder(self.refreshTime, self.openLog)
        JobDetails.setTabOrder(self.openLog, self.closeButton)

    def retranslateUi(self, JobDetails):
        JobDetails.setWindowTitle(QtGui.QApplication.translate("JobDetails", "Job Details", None, QtGui.QApplication.UnicodeUTF8))
        self.stats.setTitle(QtGui.QApplication.translate("JobDetails", "Stats", None, QtGui.QApplication.UnicodeUTF8))
        self.label_44.setText(QtGui.QApplication.translate("JobDetails", "Waiting:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobs_frames_waiting.setText(QtGui.QApplication.translate("JobDetails", "0", None, QtGui.QApplication.UnicodeUTF8))
        self.label_28.setText(QtGui.QApplication.translate("JobDetails", "Rendering:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobs_frames_rendering.setText(QtGui.QApplication.translate("JobDetails", "0", None, QtGui.QApplication.UnicodeUTF8))
        self.label_31.setText(QtGui.QApplication.translate("JobDetails", "Complete:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobs_frames_complete.setText(QtGui.QApplication.translate("JobDetails", "0", None, QtGui.QApplication.UnicodeUTF8))
        self.label_32.setText(QtGui.QApplication.translate("JobDetails", "Failed:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobs_frames_failed.setText(QtGui.QApplication.translate("JobDetails", "0", None, QtGui.QApplication.UnicodeUTF8))
        self.header.setText(QtGui.QApplication.translate("JobDetails", "Job Details", None, QtGui.QApplication.UnicodeUTF8))
        self.refreshNow.setText(QtGui.QApplication.translate("JobDetails", "Refresh", None, QtGui.QApplication.UnicodeUTF8))
        self.openLog.setText(QtGui.QApplication.translate("JobDetails", "Open Log", None, QtGui.QApplication.UnicodeUTF8))
        self.autoRefresh.setText(QtGui.QApplication.translate("JobDetails", "Auto Refresh", None, QtGui.QApplication.UnicodeUTF8))
        self.refreshTime.setSuffix(QtGui.QApplication.translate("JobDetails", "s", None, QtGui.QApplication.UnicodeUTF8))
        self.closeButton.setText(QtGui.QApplication.translate("JobDetails", "Close", None, QtGui.QApplication.UnicodeUTF8))

