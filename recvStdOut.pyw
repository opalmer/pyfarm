#!/usr/bin/python

import os
import sys
from PyQt4.QtGui import *
from PyQt4.QtCore import *
from lib.ui.recvStdOut import Ui_StdOutputServer

class RecvStdOut(QWidget):
    def __init__(self, parent=None):
        super(RecvStdOut, self).__init__(parent)
        self.ui = Ui_StdOutputServer()
        self.ui.setupUi(self)
        self.stdOut = self.ui.stdOutput

app = QApplication(sys.argv)
ui = RecvStdOut()
ui.show()
app.exec_()
