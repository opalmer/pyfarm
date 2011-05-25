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
import jsonrdp
from PyQt4 import QtNetwork, QtCore
from PyQt4.QtCore import Qt

PORT = 9407
UINT16 = 2
STREAM_VERSION = QtCore.QDataStream.Qt_4_2

class ServerThread(QtCore.QThread):
    def __init__(self, socketIdent, parent):
        super(ServerThread, self).__init__(parent)
        self.socketIdent = socketIdent

    def run(self):
        socket = QtNetwork.QTcpSocket()

        if not socket.setSocketDescriptor(self.socketIdent):
            self.emit(QtCore.SIGNAL("error(int)"), socket.error())
            return

        while socket.state() == QtNetwork.QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            packet = jsonrdp.Packet(socket)

            # setup the incoming stream
            stream = QtCore.QDataStream(socket)
            stream.setVersion(STREAM_VERSION)

            if socket.waitForReadyRead() and socket.bytesAvailable() >= UINT16:
                nextBlockSize = stream.readUInt16()

            else:
                packet.reply("ERROR", "Cannot read client request")
                return

            if socket.bytesAvailable() < nextBlockSize:
                if not socket.waitForReadyRead(60000) or socket.bytesAvailable() < nextBlockSize:
                    packet.reply("ERROR", "Cannot read client data")
                    return

            header = QtCore.QString()
            data = QtCore.QString()
            stream >> header >> data

            print "Incoming Header:",header
            print "Incoming Data:",data
            packet.reply(header, data)
            socket.waitForDisconnected()

class TcpServer(QtNetwork.QTcpServer):
    def __init__(self, parent=None):
        super(TcpServer, self).__init__(parent)

    def incomingConnection(self, socketIdent):
        print "incoming connection"
        thread = ServerThread(socketIdent, self)
        self.connect(thread, QtCore.SIGNAL("finished()"),
                     thread, QtCore.SLOT("deleteLater()"))
        thread.start()


class Main(QtCore.QObject):

    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        self.tcpServer = TcpServer(self)

        if not self.tcpServer.listen(QtNetwork.QHostAddress("0.0.0.0"), PORT):
            print "Error: %s" % self.tcpServer.errorString()
            self.close()
            return

import signal
signal.signal(signal.SIGINT, signal.SIG_DFL)
app = QtCore.QCoreApplication(sys.argv)
server = Main()
app.exec_()