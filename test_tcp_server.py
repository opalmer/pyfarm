#!/usr/bin/python

from lib.Network import *
from PyQt4.QtCore import *
from PyQt4.QtGui import *

PORT = 9631

class StartTCPServer(QPushButton):
    def __init__(self, parent=None):
        super(StartTCPServer, self).__init__("&Close Server", parent)
        self.setWindowFlags(Qt.WindowStaysOnTopHint)

        self.tcpServer = TCPServer(self)
        if not self.tcpServer.listen(QHostAddress("0.0.0.0"), PORT):
            QMessageBox.critical(self, "Building Services Server", QString("Failed to start server: %1").arg(self.tcpServer.errorString()))
            self.close()
            return

        self.connect(self, SIGNAL("clicked()"), self.close)
        font = self.font()
        font.setPointSize(24)
        self.setFont(font)
        self.setWindowTitle("Building Services Server")

app = QApplication(sys.argv)
form = StartTCPServer()
form.show()
form.move(2100, 800)
app.exec_()

#tcpServer = TCPServer()
#while tcpServer.listen(QHostAddress("0.0.0.0"), PORT)
