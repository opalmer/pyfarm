# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'QtDesigner/DropDowntest.ui'
#
# Created: Tue Feb 17 20:11:55 2009
#      by: PyQt4 UI code generator 4.4.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_DropDownMenuTest(object):
    def setupUi(self, DropDownMenuTest):
        DropDownMenuTest.setObjectName("DropDownMenuTest")
        DropDownMenuTest.resize(731, 332)
        self.softwareSelection = QtGui.QComboBox(DropDownMenuTest)
        self.softwareSelection.setGeometry(QtCore.QRect(20, 20, 75, 27))
        self.softwareSelection.setSizeAdjustPolicy(QtGui.QComboBox.AdjustToContents)
        self.softwareSelection.setObjectName("softwareSelection")
        self.optionStack = QtGui.QStackedWidget(DropDownMenuTest)
        self.optionStack.setGeometry(QtCore.QRect(40, 70, 631, 251))
        self.optionStack.setObjectName("optionStack")
        self.mayaSettings = QtGui.QWidget()
        self.mayaSettings.setObjectName("mayaSettings")
        self.layoutWidget = QtGui.QWidget(self.mayaSettings)
        self.layoutWidget.setGeometry(QtCore.QRect(20, 30, 575, 186))
        self.layoutWidget.setObjectName("layoutWidget")
        self.verticalLayout = QtGui.QVBoxLayout(self.layoutWidget)
        self.verticalLayout.setObjectName("verticalLayout")
        self.horizontalLayout = QtGui.QHBoxLayout()
        self.horizontalLayout.setObjectName("horizontalLayout")
        self.sceneLabel = QtGui.QLabel(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.sceneLabel.setFont(font)
        self.sceneLabel.setObjectName("sceneLabel")
        self.horizontalLayout.addWidget(self.sceneLabel)
        self.mayaScene = QtGui.QLineEdit(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.mayaScene.setFont(font)
        self.mayaScene.setObjectName("mayaScene")
        self.horizontalLayout.addWidget(self.mayaScene)
        self.mayaBrowseForScene = QtGui.QPushButton(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.mayaBrowseForScene.setFont(font)
        self.mayaBrowseForScene.setObjectName("mayaBrowseForScene")
        self.horizontalLayout.addWidget(self.mayaBrowseForScene)
        self.verticalLayout.addLayout(self.horizontalLayout)
        self.horizontalLayout_6 = QtGui.QHBoxLayout()
        self.horizontalLayout_6.setObjectName("horizontalLayout_6")
        self.outDirLabel = QtGui.QLabel(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.outDirLabel.setFont(font)
        self.outDirLabel.setObjectName("outDirLabel")
        self.horizontalLayout_6.addWidget(self.outDirLabel)
        self.mayaOutputDir = QtGui.QLineEdit(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.mayaOutputDir.setFont(font)
        self.mayaOutputDir.setObjectName("mayaOutputDir")
        self.horizontalLayout_6.addWidget(self.mayaOutputDir)
        self.mayaBrowseForOutputDir = QtGui.QPushButton(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.mayaBrowseForOutputDir.setFont(font)
        self.mayaBrowseForOutputDir.setObjectName("mayaBrowseForOutputDir")
        self.horizontalLayout_6.addWidget(self.mayaBrowseForOutputDir)
        self.verticalLayout.addLayout(self.horizontalLayout_6)
        self.horizontalLayout_8 = QtGui.QHBoxLayout()
        self.horizontalLayout_8.setObjectName("horizontalLayout_8")
        self.projectFileLabel = QtGui.QLabel(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.projectFileLabel.setFont(font)
        self.projectFileLabel.setObjectName("projectFileLabel")
        self.horizontalLayout_8.addWidget(self.projectFileLabel)
        self.mayaProjectFile = QtGui.QLineEdit(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.mayaProjectFile.setFont(font)
        self.mayaProjectFile.setObjectName("mayaProjectFile")
        self.horizontalLayout_8.addWidget(self.mayaProjectFile)
        self.mayaBrowseForProject = QtGui.QPushButton(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.mayaBrowseForProject.setFont(font)
        self.mayaBrowseForProject.setObjectName("mayaBrowseForProject")
        self.horizontalLayout_8.addWidget(self.mayaBrowseForProject)
        self.verticalLayout.addLayout(self.horizontalLayout_8)
        self.horizontalLayout_4 = QtGui.QHBoxLayout()
        self.horizontalLayout_4.setObjectName("horizontalLayout_4")
        spacerItem = QtGui.QSpacerItem(28, 20, QtGui.QSizePolicy.Expanding, QtGui.QSizePolicy.Minimum)
        self.horizontalLayout_4.addItem(spacerItem)
        self.useMentalRay = QtGui.QCheckBox(self.layoutWidget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.useMentalRay.setFont(font)
        self.useMentalRay.setObjectName("useMentalRay")
        self.horizontalLayout_4.addWidget(self.useMentalRay)
        self.verticalLayout.addLayout(self.horizontalLayout_4)
        self.optionStack.addWidget(self.mayaSettings)
        self.houdiniSettings = QtGui.QWidget()
        self.houdiniSettings.setObjectName("houdiniSettings")
        self.label = QtGui.QLabel(self.houdiniSettings)
        self.label.setGeometry(QtCore.QRect(240, 100, 211, 81))
        font = QtGui.QFont()
        font.setPointSize(20)
        self.label.setFont(font)
        self.label.setObjectName("label")
        self.optionStack.addWidget(self.houdiniSettings)
        self.shakeSettings = QtGui.QWidget()
        self.shakeSettings.setObjectName("shakeSettings")
        self.label_2 = QtGui.QLabel(self.shakeSettings)
        self.label_2.setGeometry(QtCore.QRect(110, 110, 211, 81))
        font = QtGui.QFont()
        font.setPointSize(20)
        self.label_2.setFont(font)
        self.label_2.setObjectName("label_2")
        self.optionStack.addWidget(self.shakeSettings)
        self.widget = QtGui.QWidget(DropDownMenuTest)
        self.widget.setGeometry(QtCore.QRect(151, 21, 471, 66))
        self.widget.setObjectName("widget")
        self.gridLayout = QtGui.QGridLayout(self.widget)
        self.gridLayout.setObjectName("gridLayout")
        self.startFrameLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.startFrameLabel.setFont(font)
        self.startFrameLabel.setObjectName("startFrameLabel")
        self.gridLayout.addWidget(self.startFrameLabel, 0, 0, 1, 1)
        self.startFrame = QtGui.QSpinBox(self.widget)
        sizePolicy = QtGui.QSizePolicy(QtGui.QSizePolicy.Fixed, QtGui.QSizePolicy.Fixed)
        sizePolicy.setHorizontalStretch(0)
        sizePolicy.setVerticalStretch(0)
        sizePolicy.setHeightForWidth(self.startFrame.sizePolicy().hasHeightForWidth())
        self.startFrame.setSizePolicy(sizePolicy)
        self.startFrame.setMinimumSize(QtCore.QSize(71, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.startFrame.setFont(font)
        self.startFrame.setCorrectionMode(QtGui.QAbstractSpinBox.CorrectToNearestValue)
        self.startFrame.setMinimum(1)
        self.startFrame.setMaximum(999999999)
        self.startFrame.setProperty("value", QtCore.QVariant(1))
        self.startFrame.setObjectName("startFrame")
        self.gridLayout.addWidget(self.startFrame, 0, 1, 1, 2)
        self.endFrameLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.endFrameLabel.setFont(font)
        self.endFrameLabel.setObjectName("endFrameLabel")
        self.gridLayout.addWidget(self.endFrameLabel, 0, 3, 1, 1)
        self.endFrame = QtGui.QSpinBox(self.widget)
        sizePolicy = QtGui.QSizePolicy(QtGui.QSizePolicy.Fixed, QtGui.QSizePolicy.Fixed)
        sizePolicy.setHorizontalStretch(0)
        sizePolicy.setVerticalStretch(0)
        sizePolicy.setHeightForWidth(self.endFrame.sizePolicy().hasHeightForWidth())
        self.endFrame.setSizePolicy(sizePolicy)
        self.endFrame.setMinimumSize(QtCore.QSize(71, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.endFrame.setFont(font)
        self.endFrame.setCorrectionMode(QtGui.QAbstractSpinBox.CorrectToNearestValue)
        self.endFrame.setMinimum(1)
        self.endFrame.setMaximum(999999999)
        self.endFrame.setObjectName("endFrame")
        self.gridLayout.addWidget(self.endFrame, 0, 4, 1, 3)
        self.byFrameLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.byFrameLabel.setFont(font)
        self.byFrameLabel.setObjectName("byFrameLabel")
        self.gridLayout.addWidget(self.byFrameLabel, 0, 7, 1, 1)
        self.byFrame = QtGui.QSpinBox(self.widget)
        sizePolicy = QtGui.QSizePolicy(QtGui.QSizePolicy.Fixed, QtGui.QSizePolicy.Fixed)
        sizePolicy.setHorizontalStretch(0)
        sizePolicy.setVerticalStretch(0)
        sizePolicy.setHeightForWidth(self.byFrame.sizePolicy().hasHeightForWidth())
        self.byFrame.setSizePolicy(sizePolicy)
        self.byFrame.setMinimumSize(QtCore.QSize(71, 28))
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.byFrame.setFont(font)
        self.byFrame.setCorrectionMode(QtGui.QAbstractSpinBox.CorrectToNearestValue)
        self.byFrame.setMinimum(1)
        self.byFrame.setMaximum(999999999)
        self.byFrame.setObjectName("byFrame")
        self.gridLayout.addWidget(self.byFrame, 0, 8, 1, 1)
        self.jobNameLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.jobNameLabel.setFont(font)
        self.jobNameLabel.setObjectName("jobNameLabel")
        self.gridLayout.addWidget(self.jobNameLabel, 1, 0, 1, 2)
        self.jobName = QtGui.QLineEdit(self.widget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.jobName.setFont(font)
        self.jobName.setObjectName("jobName")
        self.gridLayout.addWidget(self.jobName, 1, 2, 1, 3)
        self.priorityLabel = QtGui.QLabel(self.widget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.priorityLabel.setFont(font)
        self.priorityLabel.setObjectName("priorityLabel")
        self.gridLayout.addWidget(self.priorityLabel, 1, 5, 1, 1)
        self.jobPriority = QtGui.QSpinBox(self.widget)
        font = QtGui.QFont()
        font.setPointSize(10)
        font.setWeight(50)
        font.setBold(False)
        self.jobPriority.setFont(font)
        self.jobPriority.setMinimum(1)
        self.jobPriority.setMaximum(10)
        self.jobPriority.setProperty("value", QtCore.QVariant(5))
        self.jobPriority.setObjectName("jobPriority")
        self.gridLayout.addWidget(self.jobPriority, 1, 6, 1, 3)

        self.retranslateUi(DropDownMenuTest)
        self.optionStack.setCurrentIndex(0)
        QtCore.QMetaObject.connectSlotsByName(DropDownMenuTest)

    def retranslateUi(self, DropDownMenuTest):
        DropDownMenuTest.setWindowTitle(QtGui.QApplication.translate("DropDownMenuTest", "Form", None, QtGui.QApplication.UnicodeUTF8))
        self.sceneLabel.setText(QtGui.QApplication.translate("DropDownMenuTest", "Scene:", None, QtGui.QApplication.UnicodeUTF8))
        self.mayaScene.setStatusTip(QtGui.QApplication.translate("DropDownMenuTest", "Scene to render with job", None, QtGui.QApplication.UnicodeUTF8))
        self.mayaBrowseForScene.setStatusTip(QtGui.QApplication.translate("DropDownMenuTest", "Browse for scene to render", None, QtGui.QApplication.UnicodeUTF8))
        self.mayaBrowseForScene.setText(QtGui.QApplication.translate("DropDownMenuTest", "Browse", None, QtGui.QApplication.UnicodeUTF8))
        self.outDirLabel.setText(QtGui.QApplication.translate("DropDownMenuTest", "Output Dir:", None, QtGui.QApplication.UnicodeUTF8))
        self.mayaBrowseForOutputDir.setText(QtGui.QApplication.translate("DropDownMenuTest", "Browse", None, QtGui.QApplication.UnicodeUTF8))
        self.projectFileLabel.setText(QtGui.QApplication.translate("DropDownMenuTest", "Project File:", None, QtGui.QApplication.UnicodeUTF8))
        self.mayaBrowseForProject.setText(QtGui.QApplication.translate("DropDownMenuTest", "Browse", None, QtGui.QApplication.UnicodeUTF8))
        self.useMentalRay.setStatusTip(QtGui.QApplication.translate("DropDownMenuTest", "If enabled, rendering will be done with mentalray", None, QtGui.QApplication.UnicodeUTF8))
        self.useMentalRay.setText(QtGui.QApplication.translate("DropDownMenuTest", "Mental Ray", None, QtGui.QApplication.UnicodeUTF8))
        self.label.setText(QtGui.QApplication.translate("DropDownMenuTest", "HOUDINI", None, QtGui.QApplication.UnicodeUTF8))
        self.label_2.setText(QtGui.QApplication.translate("DropDownMenuTest", "SHAKE", None, QtGui.QApplication.UnicodeUTF8))
        self.startFrameLabel.setText(QtGui.QApplication.translate("DropDownMenuTest", "Start:", None, QtGui.QApplication.UnicodeUTF8))
        self.startFrame.setStatusTip(QtGui.QApplication.translate("DropDownMenuTest", "Set start frame of job", None, QtGui.QApplication.UnicodeUTF8))
        self.endFrameLabel.setText(QtGui.QApplication.translate("DropDownMenuTest", "End:", None, QtGui.QApplication.UnicodeUTF8))
        self.endFrame.setStatusTip(QtGui.QApplication.translate("DropDownMenuTest", "Set end frame of job", None, QtGui.QApplication.UnicodeUTF8))
        self.byFrameLabel.setText(QtGui.QApplication.translate("DropDownMenuTest", "By:", None, QtGui.QApplication.UnicodeUTF8))
        self.byFrame.setStatusTip(QtGui.QApplication.translate("DropDownMenuTest", "Set by or step frame of job", None, QtGui.QApplication.UnicodeUTF8))
        self.jobNameLabel.setText(QtGui.QApplication.translate("DropDownMenuTest", "Job Name:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobName.setStatusTip(QtGui.QApplication.translate("DropDownMenuTest", "Set the name of the current job", None, QtGui.QApplication.UnicodeUTF8))
        self.priorityLabel.setText(QtGui.QApplication.translate("DropDownMenuTest", "Priority:", None, QtGui.QApplication.UnicodeUTF8))
        self.jobPriority.setStatusTip(QtGui.QApplication.translate("DropDownMenuTest", "Set the job priority, 1 is low", None, QtGui.QApplication.UnicodeUTF8))

