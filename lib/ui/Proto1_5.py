# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'QtDesigner/Proto1_5.ui'
#
# Created: Wed Jan  7 18:45:03 2009
#      by: PyQt4 UI code generator 4.4.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_Proto1_5(object):
    def setupUi(self, Proto1_5):
        Proto1_5.setObjectName("Proto1_5")
        Proto1_5.resize(1004, 300)
        self.layoutWidget = QtGui.QWidget(Proto1_5)
        self.layoutWidget.setGeometry(QtCore.QRect(10, 10, 409, 281))
        self.layoutWidget.setObjectName("layoutWidget")
        self.gridLayout = QtGui.QGridLayout(self.layoutWidget)
        self.gridLayout.setObjectName("gridLayout")
        self.horizontalLayout = QtGui.QHBoxLayout()
        self.horizontalLayout.setObjectName("horizontalLayout")
        self.label = QtGui.QLabel(self.layoutWidget)
        self.label.setObjectName("label")
        self.horizontalLayout.addWidget(self.label)
        self.sceneEntry = QtGui.QLineEdit(self.layoutWidget)
        self.sceneEntry.setObjectName("sceneEntry")
        self.horizontalLayout.addWidget(self.sceneEntry)
        self.gridLayout.addLayout(self.horizontalLayout, 0, 0, 1, 1)
        self.horizontalLayout_2 = QtGui.QHBoxLayout()
        self.horizontalLayout_2.setObjectName("horizontalLayout_2")
        self.label_2 = QtGui.QLabel(self.layoutWidget)
        self.label_2.setObjectName("label_2")
        self.horizontalLayout_2.addWidget(self.label_2)
        self.sFrameBox = QtGui.QSpinBox(self.layoutWidget)
        self.sFrameBox.setMinimum(1)
        self.sFrameBox.setMaximum(999999999)
        self.sFrameBox.setObjectName("sFrameBox")
        self.horizontalLayout_2.addWidget(self.sFrameBox)
        self.label_3 = QtGui.QLabel(self.layoutWidget)
        self.label_3.setObjectName("label_3")
        self.horizontalLayout_2.addWidget(self.label_3)
        self.eFrameBox = QtGui.QSpinBox(self.layoutWidget)
        self.eFrameBox.setMinimum(1)
        self.eFrameBox.setMaximum(999999999)
        self.eFrameBox.setObjectName("eFrameBox")
        self.horizontalLayout_2.addWidget(self.eFrameBox)
        self.findNodesButton = QtGui.QPushButton(self.layoutWidget)
        self.findNodesButton.setObjectName("findNodesButton")
        self.horizontalLayout_2.addWidget(self.findNodesButton)
        self.gridLayout.addLayout(self.horizontalLayout_2, 1, 0, 1, 1)
        self.horizontalLayout_3 = QtGui.QHBoxLayout()
        self.horizontalLayout_3.setObjectName("horizontalLayout_3")
        self.hostList = QtGui.QListWidget(self.layoutWidget)
        self.hostList.setObjectName("hostList")
        self.horizontalLayout_3.addWidget(self.hostList)
        self.verticalLayout = QtGui.QVBoxLayout()
        self.verticalLayout.setObjectName("verticalLayout")
        self.aboutButton = QtGui.QPushButton(self.layoutWidget)
        self.aboutButton.setObjectName("aboutButton")
        self.verticalLayout.addWidget(self.aboutButton)
        spacerItem = QtGui.QSpacerItem(20, 40, QtGui.QSizePolicy.Minimum, QtGui.QSizePolicy.Expanding)
        self.verticalLayout.addItem(spacerItem)
        self.renderButton = QtGui.QPushButton(self.layoutWidget)
        self.renderButton.setObjectName("renderButton")
        self.verticalLayout.addWidget(self.renderButton)
        spacerItem1 = QtGui.QSpacerItem(20, 40, QtGui.QSizePolicy.Minimum, QtGui.QSizePolicy.Expanding)
        self.verticalLayout.addItem(spacerItem1)
        self.stopRender = QtGui.QPushButton(self.layoutWidget)
        self.stopRender.setObjectName("stopRender")
        self.verticalLayout.addWidget(self.stopRender)
        self.quitButton = QtGui.QPushButton(self.layoutWidget)
        self.quitButton.setObjectName("quitButton")
        self.verticalLayout.addWidget(self.quitButton)
        self.horizontalLayout_3.addLayout(self.verticalLayout)
        self.gridLayout.addLayout(self.horizontalLayout_3, 3, 0, 1, 1)
        self.horizontalLayout_4 = QtGui.QHBoxLayout()
        self.horizontalLayout_4.setObjectName("horizontalLayout_4")
        self.label_4 = QtGui.QLabel(self.layoutWidget)
        self.label_4.setObjectName("label_4")
        self.horizontalLayout_4.addWidget(self.label_4)
        self.progressBar = QtGui.QProgressBar(self.layoutWidget)
        self.progressBar.setProperty("value", QtCore.QVariant(0))
        self.progressBar.setObjectName("progressBar")
        self.horizontalLayout_4.addWidget(self.progressBar)
        self.gridLayout.addLayout(self.horizontalLayout_4, 4, 0, 1, 1)
        self.label_6 = QtGui.QLabel(self.layoutWidget)
        self.label_6.setObjectName("label_6")
        self.gridLayout.addWidget(self.label_6, 2, 0, 1, 1)
        self.layoutWidget1 = QtGui.QWidget(Proto1_5)
        self.layoutWidget1.setGeometry(QtCore.QRect(430, 10, 567, 281))
        self.layoutWidget1.setObjectName("layoutWidget1")
        self.verticalLayout_2 = QtGui.QVBoxLayout(self.layoutWidget1)
        self.verticalLayout_2.setObjectName("verticalLayout_2")
        self.horizontalLayout_5 = QtGui.QHBoxLayout()
        self.horizontalLayout_5.setObjectName("horizontalLayout_5")
        spacerItem2 = QtGui.QSpacerItem(228, 20, QtGui.QSizePolicy.Expanding, QtGui.QSizePolicy.Minimum)
        self.horizontalLayout_5.addItem(spacerItem2)
        self.label_5 = QtGui.QLabel(self.layoutWidget1)
        self.label_5.setObjectName("label_5")
        self.horizontalLayout_5.addWidget(self.label_5)
        spacerItem3 = QtGui.QSpacerItem(238, 20, QtGui.QSizePolicy.Expanding, QtGui.QSizePolicy.Minimum)
        self.horizontalLayout_5.addItem(spacerItem3)
        self.verticalLayout_2.addLayout(self.horizontalLayout_5)
        self.console = QtGui.QTextBrowser(self.layoutWidget1)
        self.console.setObjectName("console")
        self.verticalLayout_2.addWidget(self.console)

        self.retranslateUi(Proto1_5)
        QtCore.QObject.connect(self.quitButton, QtCore.SIGNAL("clicked()"), Proto1_5.close)
        QtCore.QMetaObject.connectSlotsByName(Proto1_5)

    def retranslateUi(self, Proto1_5):
        Proto1_5.setWindowTitle(QtGui.QApplication.translate("Proto1_5", "PyFarm -- Prototype  1.5", None, QtGui.QApplication.UnicodeUTF8))
        self.label.setText(QtGui.QApplication.translate("Proto1_5", "Scene:", None, QtGui.QApplication.UnicodeUTF8))
        self.label_2.setText(QtGui.QApplication.translate("Proto1_5", "Start Frame", None, QtGui.QApplication.UnicodeUTF8))
        self.label_3.setText(QtGui.QApplication.translate("Proto1_5", "End Frame", None, QtGui.QApplication.UnicodeUTF8))
        self.findNodesButton.setText(QtGui.QApplication.translate("Proto1_5", "Find Nodes", None, QtGui.QApplication.UnicodeUTF8))
        self.aboutButton.setText(QtGui.QApplication.translate("Proto1_5", "About", None, QtGui.QApplication.UnicodeUTF8))
        self.renderButton.setText(QtGui.QApplication.translate("Proto1_5", "Render", None, QtGui.QApplication.UnicodeUTF8))
        self.stopRender.setText(QtGui.QApplication.translate("Proto1_5", "Cancel Render", None, QtGui.QApplication.UnicodeUTF8))
        self.quitButton.setText(QtGui.QApplication.translate("Proto1_5", "Quit", None, QtGui.QApplication.UnicodeUTF8))
        self.label_4.setText(QtGui.QApplication.translate("Proto1_5", "Progress", None, QtGui.QApplication.UnicodeUTF8))
        self.label_6.setText(QtGui.QApplication.translate("Proto1_5", "Hosts:", None, QtGui.QApplication.UnicodeUTF8))
        self.label_5.setText(QtGui.QApplication.translate("Proto1_5", "Output Console", None, QtGui.QApplication.UnicodeUTF8))

