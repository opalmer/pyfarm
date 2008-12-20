# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file 'MainWidget.ui'
#
# Created: Fri Dec 19 16:20:04 2008
#      by: PyQt4 UI code generator 4.4.3
#
# WARNING! All changes made in this file will be lost!

from PyQt4.QtGui import *
from PyQt4.QtCore import *

class Proto1( QWidget ):
    '''First GUI generated to test the initial functions of PyFarm'''
    def __init__(self, parent=None):
        super(Proto1, self).__init__(parent)
        self.scene = QLineEdit()
        self.sFrame = QSpinBox()
        self.eFrame = QSpinBox()
        self.renderer = QComboBox()
        #self.renderers = ['Mental Ray','Software','Vector']
        self.nodeList = QTableWidget()
        self.findNodes = QPushButton()
        self.render = QPushButton()
        self.quit = QPushButton()
        self.output = QTextBrowser()
        self.progress = QProgressBar()