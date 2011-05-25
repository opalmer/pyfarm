#!/usr/bin/env python26
#
# PURPOSE: To import the standard includes and setup the package
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.


import sys

from PyQt4 import QtNetwork, QtCore
from PyQt4.QtCore import Qt

MAC = "qt_mac_set_native_menubar" in dir()

PORT = 9407
UINT16 = 2
STREAM_VERSION = QtCore.QDataStream.Qt_4_2

class Client(QtCore.QObject):
    def __init__(self, parent=None):
        super(Client, self).__init__(parent)

        self.socket = QtNetwork.QTcpSocket()
        self.nextBlockSize = 0
        self.request = None

        self.connect(self.socket, QtCore.SIGNAL("connected()"),
                     self.sendRequest)
        self.connect(self.socket, QtCore.SIGNAL("readyRead()"),
                     self.readResponse)
        self.connect(self.socket, QtCore.SIGNAL("disconnected()"),
                     self.serverStopped)
        self.connect(self.socket,
                     QtCore.SIGNAL("error(QAbstractSocket::SocketError)"),
                     self.serverError)

    def issueRequest(self, action, data):
        self.request = QtCore.QByteArray()
        stream = QtCore.QDataStream(self.request, QtCore.QIODevice.WriteOnly)
        stream.setVersion(QtCore.QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << QtCore.QString(action) << QtCore.QString(data)
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - UINT16)
        if self.socket.isOpen():
            print 'closing socket'
            self.socket.close()

        print "connecting"
        self.socket.connectToHost("localhost", PORT)

    def sendRequest(self):
        print "Sending request"
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        stream = QtCore.QDataStream(self.socket)
        stream.setVersion(STREAM_VERSION)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < UINT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break

            header = QtCore.QString()
            data = QtCore.QString()
            date = QtCore.QDate()
            stream >> header >> data
            print "Header Reply:",header
            print "Data Reply:",data
            self.nextBlockSize = 0
            self.socket.close()


    def serverStopped(self):
        print "Connection closed by server"
        self.socket.close()

    def serverError(self, error):
        print "Error: %s" % self.socket.errorString()
        self.socket.close()

import signal
signal.signal(signal.SIGINT, signal.SIG_DFL)
app = QtCore.QCoreApplication(sys.argv)
client = Client()
client.issueRequest("TEST", "hello world")
clientB = Client()
clientB.issueRequest("TEST", "hello world2")
app.exec_()