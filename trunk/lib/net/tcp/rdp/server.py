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

PORT = 9407
UINT16 = 2
STREAM_VERSION = QtCore.QDataStream.Qt_4_2


class Thread(QtCore.QThread):
    def __init__(self, socketIdent, parent):
        super(Thread, self).__init__(parent)
        self.socketIdent = socketIdent

    def run(self):
        socket = QtNetwork.QTcpSocket()

        if not socket.setSocketDescriptor(self.socketIdent):
            self.emit(QtCore.SIGNAL("error(int)"), socket.error())
            return

        while socket.state() == QtNetwork.QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QtCore.QDataStream(socket)
            stream.setVersion(STREAM_VERSION)

            if socket.waitForReadyRead() and socket.bytesAvailable() >= UINT16:
                nextBlockSize = stream.readUInt16()

            else:
                self.sendError(socket, "Cannot read client request")
                return

            if socket.bytesAvailable() < nextBlockSize:
                if not socket.waitForReadyRead(60000) or socket.bytesAvailable() < nextBlockSize:
                    self.sendError(socket, "Cannot read client data")
                    return

            header = QtCore.QString()
            data = QtCore.QString()
            stream >> header >> data

            print "Incoming Header:",header
            print "Incoming Data:",data
            self.sendReply(socket, header, data)
            socket.waitForDisconnected()

    def sendError(self, socket, msg):
        print "Sending error"
        reply = QtCore.QByteArray()
        stream = QtCore.QDataStream(reply, QtCore.QIODevice.WriteOnly)
        stream.setVersion(STREAM_VERSION)
        stream.writeUInt16(0)
        stream << QtCore.QString("ERROR") << QtCore.QString(msg)
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UINT16)
        socket.write(reply)
        print "error sent"


    def sendReply(self, socket, header, data):
        print "Sending reply"
        reply = QtCore.QByteArray()
        stream = QtCore.QDataStream(reply, QtCore.QIODevice.WriteOnly)
        stream.setVersion(STREAM_VERSION)
        stream.writeUInt16(0)
        stream << header << data
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UINT16)
        socket.write(reply)
        print "reply sent"


class TcpServer(QtNetwork.QTcpServer):
    def __init__(self, parent=None):
        super(TcpServer, self).__init__(parent)


    def incomingConnection(self, socketIdent):
        thread = Thread(socketIdent, self)
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