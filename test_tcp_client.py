#!/usr/bin/python

from lib.Network import *
from PyQt4.QtCore import *
from PyQt4.QtGui import *

app = QApplication(sys.argv)
form = TCPClient()
form.show()
app.exec_()
