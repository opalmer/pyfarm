# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'QtDesigner/RC1.ui'
#
# Created: Sat Jan 24 00:47:28 2009
#      by: PyQt4 UI code generator 4.4.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_RC1(object):
    def setupUi(self, RC1):
        RC1.setObjectName("RC1")
        RC1.setWindowModality(QtCore.Qt.WindowModal)
        RC1.setEnabled(True)
        RC1.resize(1243, 448)
        self.centralwidget = QtGui.QWidget(RC1)
        self.centralwidget.setObjectName("centralwidget")
        self.layoutWidget = QtGui.QWidget(self.centralwidget)
        self.layoutWidget.setGeometry(QtCore.QRect(30, 20, 1191, 371))
        self.layoutWidget.setObjectName("layoutWidget")
        self.horizontalLayout_5 = QtGui.QHBoxLayout(self.layoutWidget)
        self.horizontalLayout_5.setObjectName("horizontalLayout_5")
        self.tabToolbox = QtGui.QTabWidget(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(12)
        font.setWeight(75)
        font.setBold(True)
        self.tabToolbox.setFont(font)
        self.tabToolbox.setObjectName("tabToolbox")
        self.networkToolbox = QtGui.QWidget()
        self.networkToolbox.setObjectName("networkToolbox")
        self.networkTable = QtGui.QTableWidget(self.networkToolbox)
        self.networkTable.setGeometry(QtCore.QRect(20, 20, 411, 231))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.networkTable.setFont(font)
        self.networkTable.setEditTriggers(QtGui.QAbstractItemView.NoEditTriggers)
        self.networkTable.setDragDropOverwriteMode(False)
        self.networkTable.setSelectionMode(QtGui.QAbstractItemView.ContiguousSelection)
        self.networkTable.setSelectionBehavior(QtGui.QAbstractItemView.SelectRows)
        self.networkTable.setObjectName("networkTable")
        self.networkTable.setColumnCount(3)
        self.networkTable.setRowCount(0)
        item = QtGui.QTableWidgetItem()
        self.networkTable.setHorizontalHeaderItem(0, item)
        item = QtGui.QTableWidgetItem()
        self.networkTable.setHorizontalHeaderItem(1, item)
        item = QtGui.QTableWidgetItem()
        self.networkTable.setHorizontalHeaderItem(2, item)
        self.findHosts = QtGui.QPushButton(self.networkToolbox)
        self.findHosts.setGeometry(QtCore.QRect(440, 20, 91, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.findHosts.setFont(font)
        self.findHosts.setObjectName("findHosts")
        self.addHost = QtGui.QPushButton(self.networkToolbox)
        self.addHost.setGeometry(QtCore.QRect(440, 60, 91, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.addHost.setFont(font)
        self.addHost.setObjectName("addHost")
        self.hostControls = QtGui.QGroupBox(self.networkToolbox)
        self.hostControls.setGeometry(QtCore.QRect(20, 260, 411, 71))
        font = QtGui.QFont()
        font.setPointSize(10)
        self.hostControls.setFont(font)
        self.hostControls.setObjectName("hostControls")
        self.removeHost = QtGui.QPushButton(self.hostControls)
        self.removeHost.setGeometry(QtCore.QRect(310, 30, 91, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.removeHost.setFont(font)
        self.removeHost.setObjectName("removeHost")
        self.disableHost = QtGui.QPushButton(self.hostControls)
        self.disableHost.setGeometry(QtCore.QRect(110, 30, 91, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.disableHost.setFont(font)
        self.disableHost.setObjectName("disableHost")
        self.editHost = QtGui.QPushButton(self.hostControls)
        self.editHost.setGeometry(QtCore.QRect(210, 30, 91, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.editHost.setFont(font)
        self.editHost.setObjectName("editHost")
        self.getHostInfo = QtGui.QPushButton(self.hostControls)
        self.getHostInfo.setGeometry(QtCore.QRect(10, 30, 91, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.getHostInfo.setFont(font)
        self.getHostInfo.setObjectName("getHostInfo")
        self.tabToolbox.addTab(self.networkToolbox, "")
        self.submitToolbox = QtGui.QWidget()
        self.submitToolbox.setObjectName("submitToolbox")
        self.submitJob = QtGui.QPushButton(self.submitToolbox)
        self.submitJob.setGeometry(QtCore.QRect(500, 430, 80, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.submitJob.setFont(font)
        self.submitJob.setObjectName("submitJob")
        self.layoutWidget1 = QtGui.QWidget(self.submitToolbox)
        self.layoutWidget1.setGeometry(QtCore.QRect(12, 26, 575, 109))
        self.layoutWidget1.setObjectName("layoutWidget1")
        self.verticalLayout = QtGui.QVBoxLayout(self.layoutWidget1)
        self.verticalLayout.setObjectName("verticalLayout")
        self.horizontalLayout = QtGui.QHBoxLayout()
        self.horizontalLayout.setObjectName("horizontalLayout")
        self.sceneLabel = QtGui.QLabel(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.sceneLabel.setFont(font)
        self.sceneLabel.setObjectName("sceneLabel")
        self.horizontalLayout.addWidget(self.sceneLabel)
        self.inputScene = QtGui.QLineEdit(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.inputScene.setFont(font)
        self.inputScene.setObjectName("inputScene")
        self.horizontalLayout.addWidget(self.inputScene)
        self.browseForScene = QtGui.QPushButton(self.layoutWidget1)
        self.browseForScene.setEnabled(False)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.browseForScene.setFont(font)
        self.browseForScene.setObjectName("browseForScene")
        self.horizontalLayout.addWidget(self.browseForScene)
        self.verticalLayout.addLayout(self.horizontalLayout)
        self.horizontalLayout_2 = QtGui.QHBoxLayout()
        self.horizontalLayout_2.setObjectName("horizontalLayout_2")
        self.jobNameLabel = QtGui.QLabel(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.jobNameLabel.setFont(font)
        self.jobNameLabel.setObjectName("jobNameLabel")
        self.horizontalLayout_2.addWidget(self.jobNameLabel)
        self.inputJobName = QtGui.QLineEdit(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.inputJobName.setFont(font)
        self.inputJobName.setObjectName("inputJobName")
        self.horizontalLayout_2.addWidget(self.inputJobName)
        self.softwarePackagesLabel = QtGui.QLabel(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.softwarePackagesLabel.setFont(font)
        self.softwarePackagesLabel.setObjectName("softwarePackagesLabel")
        self.horizontalLayout_2.addWidget(self.softwarePackagesLabel)
        self.softwarePackages = QtGui.QComboBox(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.softwarePackages.setFont(font)
        self.softwarePackages.setObjectName("softwarePackages")
        self.softwarePackages.addItem(QtCore.QString())
        self.softwarePackages.addItem(QtCore.QString())
        self.softwarePackages.addItem(QtCore.QString())
        self.horizontalLayout_2.addWidget(self.softwarePackages)
        self.priorityLabel = QtGui.QLabel(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.priorityLabel.setFont(font)
        self.priorityLabel.setObjectName("priorityLabel")
        self.horizontalLayout_2.addWidget(self.priorityLabel)
        self.inputJobPriority = QtGui.QSpinBox(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.inputJobPriority.setFont(font)
        self.inputJobPriority.setMinimum(1)
        self.inputJobPriority.setMaximum(10)
        self.inputJobPriority.setProperty("value", QtCore.QVariant(5))
        self.inputJobPriority.setObjectName("inputJobPriority")
        self.horizontalLayout_2.addWidget(self.inputJobPriority)
        self.verticalLayout.addLayout(self.horizontalLayout_2)
        self.horizontalLayout_4 = QtGui.QHBoxLayout()
        self.horizontalLayout_4.setObjectName("horizontalLayout_4")
        spacerItem = QtGui.QSpacerItem(28, 20, QtGui.QSizePolicy.Expanding, QtGui.QSizePolicy.Minimum)
        self.horizontalLayout_4.addItem(spacerItem)
        self.horizontalLayout_3 = QtGui.QHBoxLayout()
        self.horizontalLayout_3.setObjectName("horizontalLayout_3")
        self.startFrameLabel = QtGui.QLabel(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.startFrameLabel.setFont(font)
        self.startFrameLabel.setObjectName("startFrameLabel")
        self.horizontalLayout_3.addWidget(self.startFrameLabel)
        self.inputStartFrame = QtGui.QSpinBox(self.layoutWidget1)
        sizePolicy = QtGui.QSizePolicy(QtGui.QSizePolicy.Fixed, QtGui.QSizePolicy.Fixed)
        sizePolicy.setHorizontalStretch(0)
        sizePolicy.setVerticalStretch(0)
        sizePolicy.setHeightForWidth(self.inputStartFrame.sizePolicy().hasHeightForWidth())
        self.inputStartFrame.setSizePolicy(sizePolicy)
        self.inputStartFrame.setMinimumSize(QtCore.QSize(71, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.inputStartFrame.setFont(font)
        self.inputStartFrame.setCorrectionMode(QtGui.QAbstractSpinBox.CorrectToNearestValue)
        self.inputStartFrame.setMinimum(1)
        self.inputStartFrame.setMaximum(500000000)
        self.inputStartFrame.setProperty("value", QtCore.QVariant(1))
        self.inputStartFrame.setObjectName("inputStartFrame")
        self.horizontalLayout_3.addWidget(self.inputStartFrame)
        self.endFrameLabel = QtGui.QLabel(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.endFrameLabel.setFont(font)
        self.endFrameLabel.setObjectName("endFrameLabel")
        self.horizontalLayout_3.addWidget(self.endFrameLabel)
        self.inputEndFrame = QtGui.QSpinBox(self.layoutWidget1)
        sizePolicy = QtGui.QSizePolicy(QtGui.QSizePolicy.Fixed, QtGui.QSizePolicy.Fixed)
        sizePolicy.setHorizontalStretch(0)
        sizePolicy.setVerticalStretch(0)
        sizePolicy.setHeightForWidth(self.inputEndFrame.sizePolicy().hasHeightForWidth())
        self.inputEndFrame.setSizePolicy(sizePolicy)
        self.inputEndFrame.setMinimumSize(QtCore.QSize(71, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.inputEndFrame.setFont(font)
        self.inputEndFrame.setCorrectionMode(QtGui.QAbstractSpinBox.CorrectToNearestValue)
        self.inputEndFrame.setMinimum(1)
        self.inputEndFrame.setObjectName("inputEndFrame")
        self.horizontalLayout_3.addWidget(self.inputEndFrame)
        self.endFrameLabel_2 = QtGui.QLabel(self.layoutWidget1)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.endFrameLabel_2.setFont(font)
        self.endFrameLabel_2.setObjectName("endFrameLabel_2")
        self.horizontalLayout_3.addWidget(self.endFrameLabel_2)
        self.inputByFrame = QtGui.QSpinBox(self.layoutWidget1)
        sizePolicy = QtGui.QSizePolicy(QtGui.QSizePolicy.Fixed, QtGui.QSizePolicy.Fixed)
        sizePolicy.setHorizontalStretch(0)
        sizePolicy.setVerticalStretch(0)
        sizePolicy.setHeightForWidth(self.inputByFrame.sizePolicy().hasHeightForWidth())
        self.inputByFrame.setSizePolicy(sizePolicy)
        self.inputByFrame.setMinimumSize(QtCore.QSize(71, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.inputByFrame.setFont(font)
        self.inputByFrame.setCorrectionMode(QtGui.QAbstractSpinBox.CorrectToNearestValue)
        self.inputByFrame.setMinimum(1)
        self.inputByFrame.setObjectName("inputByFrame")
        self.horizontalLayout_3.addWidget(self.inputByFrame)
        self.horizontalLayout_4.addLayout(self.horizontalLayout_3)
        spacerItem1 = QtGui.QSpacerItem(188, 20, QtGui.QSizePolicy.Expanding, QtGui.QSizePolicy.Minimum)
        self.horizontalLayout_4.addItem(spacerItem1)
        self.verticalLayout.addLayout(self.horizontalLayout_4)
        self.tabToolbox.addTab(self.submitToolbox, "")
        self.jobsToolbox = QtGui.QWidget()
        self.jobsToolbox.setObjectName("jobsToolbox")
        self.tableWidget = QtGui.QTableWidget(self.jobsToolbox)
        self.tableWidget.setGeometry(QtCore.QRect(20, 50, 611, 192))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.tableWidget.setFont(font)
        self.tableWidget.setObjectName("tableWidget")
        self.tableWidget.setColumnCount(6)
        self.tableWidget.setRowCount(0)
        item = QtGui.QTableWidgetItem()
        self.tableWidget.setHorizontalHeaderItem(0, item)
        item = QtGui.QTableWidgetItem()
        self.tableWidget.setHorizontalHeaderItem(1, item)
        item = QtGui.QTableWidgetItem()
        self.tableWidget.setHorizontalHeaderItem(2, item)
        item = QtGui.QTableWidgetItem()
        self.tableWidget.setHorizontalHeaderItem(3, item)
        item = QtGui.QTableWidgetItem()
        self.tableWidget.setHorizontalHeaderItem(4, item)
        item = QtGui.QTableWidgetItem()
        self.tableWidget.setHorizontalHeaderItem(5, item)
        self.tabToolbox.addTab(self.jobsToolbox, "")
        self.statusToolbox = QtGui.QWidget()
        self.statusToolbox.setObjectName("statusToolbox")
        self.tabToolbox.addTab(self.statusToolbox, "")
        self.horizontalLayout_5.addWidget(self.tabToolbox)
        self.render = QtGui.QPushButton(self.layoutWidget)
        self.render.setObjectName("render")
        self.horizontalLayout_5.addWidget(self.render)
        self.status = QtGui.QTextBrowser(self.layoutWidget)
        self.status.setMaximumSize(QtCore.QSize(450, 16777215))
        self.status.setObjectName("status")
        self.horizontalLayout_5.addWidget(self.status)
        RC1.setCentralWidget(self.centralwidget)
        self.menubar = QtGui.QMenuBar(RC1)
        self.menubar.setGeometry(QtCore.QRect(0, 0, 1243, 26))
        self.menubar.setObjectName("menubar")
        self.menuFile = QtGui.QMenu(self.menubar)
        self.menuFile.setObjectName("menuFile")
        self.menuHelp = QtGui.QMenu(self.menubar)
        self.menuHelp.setObjectName("menuHelp")
        RC1.setMenuBar(self.menubar)
        self.statusbar = QtGui.QStatusBar(RC1)
        self.statusbar.setObjectName("statusbar")
        RC1.setStatusBar(self.statusbar)
        self.menuFile_openSettings = QtGui.QAction(RC1)
        self.menuFile_openSettings.setEnabled(False)
        self.menuFile_openSettings.setObjectName("menuFile_openSettings")
        self.menuFile_quit = QtGui.QAction(RC1)
        self.menuFile_quit.setObjectName("menuFile_quit")
        self.menuFile_saveSettings = QtGui.QAction(RC1)
        self.menuFile_saveSettings.setEnabled(False)
        self.menuFile_saveSettings.setObjectName("menuFile_saveSettings")
        self.menuNodes_findNodes = QtGui.QAction(RC1)
        self.menuNodes_findNodes.setObjectName("menuNodes_findNodes")
        self.menuNodes_addNode = QtGui.QAction(RC1)
        self.menuNodes_addNode.setEnabled(False)
        self.menuNodes_addNode.setObjectName("menuNodes_addNode")
        self.menuNodes_stopNodes = QtGui.QAction(RC1)
        self.menuNodes_stopNodes.setEnabled(False)
        self.menuNodes_stopNodes.setObjectName("menuNodes_stopNodes")
        self.menuHelp_about = QtGui.QAction(RC1)
        self.menuHelp_about.setObjectName("menuHelp_about")
        self.menuHelp_docs = QtGui.QAction(RC1)
        self.menuHelp_docs.setObjectName("menuHelp_docs")
        self.menuHelp_updates = QtGui.QAction(RC1)
        self.menuHelp_updates.setEnabled(False)
        self.menuHelp_updates.setObjectName("menuHelp_updates")
        self.menuHelp_bugs = QtGui.QAction(RC1)
        self.menuHelp_bugs.setObjectName("menuHelp_bugs")
        self.menuHelp_diagnostics = QtGui.QAction(RC1)
        self.menuHelp_diagnostics.setEnabled(False)
        self.menuHelp_diagnostics.setObjectName("menuHelp_diagnostics")
        self.menuFile.addAction(self.menuFile_openSettings)
        self.menuFile.addAction(self.menuFile_saveSettings)
        self.menuFile.addSeparator()
        self.menuFile.addAction(self.menuFile_quit)
        self.menuHelp.addAction(self.menuHelp_about)
        self.menuHelp.addAction(self.menuHelp_updates)
        self.menuHelp.addSeparator()
        self.menuHelp.addAction(self.menuHelp_docs)
        self.menuHelp.addAction(self.menuHelp_bugs)
        self.menuHelp.addSeparator()
        self.menuHelp.addAction(self.menuHelp_diagnostics)
        self.menubar.addAction(self.menuFile.menuAction())
        self.menubar.addAction(self.menuHelp.menuAction())

        self.retranslateUi(RC1)
        self.tabToolbox.setCurrentIndex(0)
        QtCore.QObject.connect(self.menuFile_quit, QtCore.SIGNAL("triggered()"), RC1.close)
        QtCore.QMetaObject.connectSlotsByName(RC1)

    def retranslateUi(self, RC1):
        RC1.setWindowTitle(QtGui.QApplication.translate("RC1", "PyFarm -- Release Canidate 1", None, QtGui.QApplication.UnicodeUTF8))
        self.networkTable.setStatusTip(QtGui.QApplication.translate("RC1", "Connected Hosts", None, QtGui.QApplication.UnicodeUTF8))
        self.networkTable.horizontalHeaderItem(0).setText(QtGui.QApplication.translate("RC1", "Hostname", None, QtGui.QApplication.UnicodeUTF8))
        self.networkTable.horizontalHeaderItem(1).setText(QtGui.QApplication.translate("RC1", "IP Address", None, QtGui.QApplication.UnicodeUTF8))
        self.networkTable.horizontalHeaderItem(2).setText(QtGui.QApplication.translate("RC1", "Status", None, QtGui.QApplication.UnicodeUTF8))
        self.findHosts.setStatusTip(QtGui.QApplication.translate("RC1", "Find then automatically add hosts running the client program", None, QtGui.QApplication.UnicodeUTF8))
        self.findHosts.setText(QtGui.QApplication.translate("RC1", "Find Hosts", None, QtGui.QApplication.UnicodeUTF8))
        self.addHost.setStatusTip(QtGui.QApplication.translate("RC1", "Manually add a host", None, QtGui.QApplication.UnicodeUTF8))
        self.addHost.setText(QtGui.QApplication.translate("RC1", "Add Host", None, QtGui.QApplication.UnicodeUTF8))
        self.hostControls.setTitle(QtGui.QApplication.translate("RC1", "Host Controls", None, QtGui.QApplication.UnicodeUTF8))
        self.removeHost.setStatusTip(QtGui.QApplication.translate("RC1", "Remove selected host from pool", None, QtGui.QApplication.UnicodeUTF8))
        self.removeHost.setText(QtGui.QApplication.translate("RC1", "Remove", None, QtGui.QApplication.UnicodeUTF8))
        self.disableHost.setStatusTip(QtGui.QApplication.translate("RC1", "Disable selected host but do not remove it", None, QtGui.QApplication.UnicodeUTF8))
        self.disableHost.setText(QtGui.QApplication.translate("RC1", "Disable", None, QtGui.QApplication.UnicodeUTF8))
        self.editHost.setStatusTip(QtGui.QApplication.translate("RC1", "Edit selected host\'s parameters", None, QtGui.QApplication.UnicodeUTF8))
        self.editHost.setText(QtGui.QApplication.translate("RC1", "Edit", None, QtGui.QApplication.UnicodeUTF8))
        self.getHostInfo.setStatusTip(QtGui.QApplication.translate("RC1", "Retrieve extra info about selected host", None, QtGui.QApplication.UnicodeUTF8))
        self.getHostInfo.setText(QtGui.QApplication.translate("RC1", "Info", None, QtGui.QApplication.UnicodeUTF8))
        self.tabToolbox.setTabText(self.tabToolbox.indexOf(self.networkToolbox), QtGui.QApplication.translate("RC1", "Network", None, QtGui.QApplication.UnicodeUTF8))
        self.tabToolbox.setTabToolTip(self.tabToolbox.indexOf(self.networkToolbox), QtGui.QApplication.translate("RC1", "Gather, manage, and update nodes for PyFarm", None, QtGui.QApplication.UnicodeUTF8))
        self.submitJob.setStatusTip(QtGui.QApplication.translate("RC1", "Submit job to farm for rendering", None, QtGui.QApplication.UnicodeUTF8))
        self.submitJob.setText(QtGui.QApplication.translate("RC1", "Submit", None, QtGui.QApplication.UnicodeUTF8))
        self.sceneLabel.setText(QtGui.QApplication.translate("RC1", "Scene:", None, QtGui.QApplication.UnicodeUTF8))
        self.inputScene.setStatusTip(QtGui.QApplication.translate("RC1", "Scene to render with job", None, QtGui.QApplication.UnicodeUTF8))
        self.browseForScene.setStatusTip(QtGui.QApplication.translate("RC1", "Browse for scene to render [ Not implimented yet ]", None, QtGui.QApplication.UnicodeUTF8))
        self.browseForScene.setText(QtGui.QApplication.translate("RC1", "Browse", None, QtGui.QApplication.UnicodeUTF8))
        self.jobNameLabel.setText(QtGui.QApplication.translate("RC1", "Job Name:", None, QtGui.QApplication.UnicodeUTF8))
        self.inputJobName.setStatusTip(QtGui.QApplication.translate("RC1", "Set the name of the current job", None, QtGui.QApplication.UnicodeUTF8))
        self.softwarePackagesLabel.setText(QtGui.QApplication.translate("RC1", "Software:", None, QtGui.QApplication.UnicodeUTF8))
        self.softwarePackages.setStatusTip(QtGui.QApplication.translate("RC1", "Built in software types, not all software may be installed on your system", None, QtGui.QApplication.UnicodeUTF8))
        self.softwarePackages.setItemText(0, QtGui.QApplication.translate("RC1", "Maya 2008", None, QtGui.QApplication.UnicodeUTF8))
        self.softwarePackages.setItemText(1, QtGui.QApplication.translate("RC1", "Maya 2009", None, QtGui.QApplication.UnicodeUTF8))
        self.softwarePackages.setItemText(2, QtGui.QApplication.translate("RC1", "Shake", None, QtGui.QApplication.UnicodeUTF8))
        self.priorityLabel.setText(QtGui.QApplication.translate("RC1", "Priority:", None, QtGui.QApplication.UnicodeUTF8))
        self.inputJobPriority.setStatusTip(QtGui.QApplication.translate("RC1", "Set the job priority, 1 is low", None, QtGui.QApplication.UnicodeUTF8))
        self.startFrameLabel.setText(QtGui.QApplication.translate("RC1", "Start:", None, QtGui.QApplication.UnicodeUTF8))
        self.inputStartFrame.setStatusTip(QtGui.QApplication.translate("RC1", "Set start frame of job", None, QtGui.QApplication.UnicodeUTF8))
        self.endFrameLabel.setText(QtGui.QApplication.translate("RC1", "End:", None, QtGui.QApplication.UnicodeUTF8))
        self.inputEndFrame.setStatusTip(QtGui.QApplication.translate("RC1", "Set end frame of job", None, QtGui.QApplication.UnicodeUTF8))
        self.endFrameLabel_2.setText(QtGui.QApplication.translate("RC1", "By:", None, QtGui.QApplication.UnicodeUTF8))
        self.inputByFrame.setStatusTip(QtGui.QApplication.translate("RC1", "Set by or step frame of job", None, QtGui.QApplication.UnicodeUTF8))
        self.tabToolbox.setTabText(self.tabToolbox.indexOf(self.submitToolbox), QtGui.QApplication.translate("RC1", "Submit", None, QtGui.QApplication.UnicodeUTF8))
        self.tabToolbox.setTabToolTip(self.tabToolbox.indexOf(self.submitToolbox), QtGui.QApplication.translate("RC1", "Setup and submit a render to PyFarm", None, QtGui.QApplication.UnicodeUTF8))
        self.tableWidget.horizontalHeaderItem(0).setText(QtGui.QApplication.translate("RC1", "ID", None, QtGui.QApplication.UnicodeUTF8))
        self.tableWidget.horizontalHeaderItem(1).setText(QtGui.QApplication.translate("RC1", "Name", None, QtGui.QApplication.UnicodeUTF8))
        self.tableWidget.horizontalHeaderItem(2).setText(QtGui.QApplication.translate("RC1", "Status", None, QtGui.QApplication.UnicodeUTF8))
        self.tableWidget.horizontalHeaderItem(3).setText(QtGui.QApplication.translate("RC1", "Rendering", None, QtGui.QApplication.UnicodeUTF8))
        self.tableWidget.horizontalHeaderItem(4).setText(QtGui.QApplication.translate("RC1", "Complete", None, QtGui.QApplication.UnicodeUTF8))
        self.tableWidget.horizontalHeaderItem(5).setText(QtGui.QApplication.translate("RC1", "Failures", None, QtGui.QApplication.UnicodeUTF8))
        self.tabToolbox.setTabText(self.tabToolbox.indexOf(self.jobsToolbox), QtGui.QApplication.translate("RC1", "Jobs", None, QtGui.QApplication.UnicodeUTF8))
        self.tabToolbox.setTabToolTip(self.tabToolbox.indexOf(self.jobsToolbox), QtGui.QApplication.translate("RC1", "View curently active jobs", None, QtGui.QApplication.UnicodeUTF8))
        self.tabToolbox.setTabText(self.tabToolbox.indexOf(self.statusToolbox), QtGui.QApplication.translate("RC1", "Status", None, QtGui.QApplication.UnicodeUTF8))
        self.tabToolbox.setTabToolTip(self.tabToolbox.indexOf(self.statusToolbox), QtGui.QApplication.translate("RC1", "General status overview of PyFarm", None, QtGui.QApplication.UnicodeUTF8))
        self.render.setText(QtGui.QApplication.translate("RC1", "Render", None, QtGui.QApplication.UnicodeUTF8))
        self.menuFile.setTitle(QtGui.QApplication.translate("RC1", "File", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp.setTitle(QtGui.QApplication.translate("RC1", "Help", None, QtGui.QApplication.UnicodeUTF8))
        self.menuFile_openSettings.setText(QtGui.QApplication.translate("RC1", "Open Settings", None, QtGui.QApplication.UnicodeUTF8))
        self.menuFile_openSettings.setToolTip(QtGui.QApplication.translate("RC1", "Open Settings", None, QtGui.QApplication.UnicodeUTF8))
        self.menuFile_openSettings.setStatusTip(QtGui.QApplication.translate("RC1", "Open settings from a previous session [ Not implimented yet ]", None, QtGui.QApplication.UnicodeUTF8))
        self.menuFile_quit.setText(QtGui.QApplication.translate("RC1", "Quit", None, QtGui.QApplication.UnicodeUTF8))
        self.menuFile_quit.setToolTip(QtGui.QApplication.translate("RC1", "Quit", None, QtGui.QApplication.UnicodeUTF8))
        self.menuFile_quit.setStatusTip(QtGui.QApplication.translate("RC1", "Exit the program and kill active renders", None, QtGui.QApplication.UnicodeUTF8))
        self.menuFile_saveSettings.setText(QtGui.QApplication.translate("RC1", "Save Settings", None, QtGui.QApplication.UnicodeUTF8))
        self.menuFile_saveSettings.setStatusTip(QtGui.QApplication.translate("RC1", "Save your current settings for later use [ Not implimented yet ]", None, QtGui.QApplication.UnicodeUTF8))
        self.menuNodes_findNodes.setText(QtGui.QApplication.translate("RC1", "Find Nodes", None, QtGui.QApplication.UnicodeUTF8))
        self.menuNodes_findNodes.setStatusTip(QtGui.QApplication.translate("RC1", "Find all available nodes running the client program", None, QtGui.QApplication.UnicodeUTF8))
        self.menuNodes_addNode.setText(QtGui.QApplication.translate("RC1", "Add New Node", None, QtGui.QApplication.UnicodeUTF8))
        self.menuNodes_addNode.setStatusTip(QtGui.QApplication.translate("RC1", "Add a custom node to the render farm [ Not implimented yet ]", None, QtGui.QApplication.UnicodeUTF8))
        self.menuNodes_stopNodes.setText(QtGui.QApplication.translate("RC1", "Stop Nodes", None, QtGui.QApplication.UnicodeUTF8))
        self.menuNodes_stopNodes.setStatusTip(QtGui.QApplication.translate("RC1", "Stop all renders on the active node pool [ Not implimented yet ]", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_about.setText(QtGui.QApplication.translate("RC1", "About", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_about.setStatusTip(QtGui.QApplication.translate("RC1", "About this program", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_docs.setText(QtGui.QApplication.translate("RC1", "Documentation", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_docs.setStatusTip(QtGui.QApplication.translate("RC1", "Open PyFarm\'s documentation (requires internet access)", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_updates.setText(QtGui.QApplication.translate("RC1", "Check for Updates", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_updates.setStatusTip(QtGui.QApplication.translate("RC1", "Check for the most recent version of this program online [ Not implimented yet ]", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_bugs.setText(QtGui.QApplication.translate("RC1", "Bug Report", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_bugs.setStatusTip(QtGui.QApplication.translate("RC1", "Submit and review the latest bugs for PyFarm", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_diagnostics.setText(QtGui.QApplication.translate("RC1", "Diagnostic Utilities", None, QtGui.QApplication.UnicodeUTF8))
        self.menuHelp_diagnostics.setStatusTip(QtGui.QApplication.translate("RC1", "Utilities used to perform basic checks on PyFarm\'s health [ Not implimented yet ]", None, QtGui.QApplication.UnicodeUTF8))

