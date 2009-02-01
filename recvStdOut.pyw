#!/usr/bin/python

import os
import sys
from PyQt4.QtGui import *
from PyQt4.QtCore import *
from lib.ui.recvStdOut import Ui_StdOutputServer
from lib.Network import *

TCP_PORT = 9631

class RecvStdOut(QWidget):
    def __init__(self, parent=None):
        super(RecvStdOut, self).__init__(parent)
        self.ui = Ui_StdOutputServer()
        self.ui.setupUi(self)
        self.stdOut = self.ui.stdOutput
        self.server = TCPServerStdOut2(self)
        self.line = 1
        self.connect(self.server, SIGNAL("emitStdOutLine"), self.logLine)
        self.server.listen(QHostAddress("10.56.1.91"), TCP_PORT)

    def logLine(self, line):
        #print list(line)[3].replace('\n', '')
        self.stdOut.append("<b>%i</b> - %s"% (self.line, list(line)[3].replace('\n', '')))
        self.line += 1

app = QApplication(sys.argv)
ui = RecvStdOut()
ui.move(800, 200)
ui.show()
app.exec_()
