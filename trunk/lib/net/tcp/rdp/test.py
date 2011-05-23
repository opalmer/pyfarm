#!/usr/bin/env python
#
# INITIAL: Jan 12 2009
# PURPOSE: Main program to run and manage PyFarm
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

import json
from PyQt4 import QtCore, QtNetwork

PORT = 9500
UINT16 = 2
STREAM_VERSION = QtCore.QDataStream.Qt_4_2

class SocketWrite(object):
    def __init__(self, socket, eader):
        self.socket = socket
        self.header = QtCore.QString(header)

    def send(self, msg, fields=[]):
        '''
        Send the given message to the client

        @param msg: The message to send
        @type  msg: C{str}
        @param fields: Additional fields to pack and send
        @type  fields: C{list}
        '''
        msg = QtCore.QString(msg)
        reply = QtCore.QByteArray()
        stream = QtCore.QDataStream(reply, QtCore.QIODevice.WriteOnly)
        stream.setVersion(STREAM_VERSION)

        # open stream and write the header
        stream.writeUInt16(0)
        stream << self.header << msg

        # pack additional fields
        for field in fields:
            stream << QtCore.QString(field)

        # close and write reply to the socket
        stream.writeUInt16(reply.size() - UINT16)
        socket.write(reply)


class ServerThread(QtCore.QThread):
    def __init__(self, socketIdent, parent=None):
        super(ServerThread, self).__init__(parent)
        self.socketIdent = socketIdent

    def run(self):
        socket = QtNetwork.QTcpSocket()

        if not socket.setSocketDescriptor(self.socketIdent):
            self.emit(QtCore.SIGNAL("error(int)"))
            return

        # so long as we are connected, work with the socket
        while socket.state() == QtNetwork.QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QtCore.QDataStream(socket)
            stream.setVersion(STREAM_VERSION)

            # ...but only so long as we have data work with
            if socket.waitForReadyRead() and socket.bytesAvailable() >= UINT16:
                nextBlockSize = stream.readUInt16()

            else:
                error = SocketWrite(socket, "ERROR")
                error.send("Cannot read client request")
                return

            if socket.bytesAvailable() < nextBlockSize:
                readyWait = socket.waitForReadyRead(60000)
                bytesAvail = socket.bytesAvailable()

                if not readyWait or bytesAvail < nextBlockSize:
                    error = SocketWrite(socket, "ERROR")
                    error.send("Cannot read client data")
                    return

            # TODO: Re-eval the purpose of the locks

class Server(QtNetwork.QTcpServer):
    def __init__(self, parent=None):
        super(Server, self).__init__(parent)

    def incomingConnection(self, socketIdent):
        '''
        Given the socket identifier thread the inoming
        connection and transfer all processing to ServerThread
        '''
        thread = ServerThread(socketIdent, self)
        self.connect(
                        thread, QtCore.SIGNAL("finished()"),
                        thread, QtCore.SLOT("deleteLater()")
                     )
        thread.start()

if __name__ == '__main__':
    print "Running"
