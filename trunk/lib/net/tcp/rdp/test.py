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

PORT = 54550
UINT16 = 2
STREAM_VERSION = QtCore.QDataStream.Qt_4_2

class SocketReply(object):
    def __init__(self, socket, header):
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
        self.socket.write(reply)


class Client(QtCore.QObject):
    def __init__(self, host, header, parent=None):
        super(Client, self).__init__(parent)
        self.socket = QtNetwork.QTcpSocket()
        self.header = QtCore.QString(header)
        self.host = host
        self.nextBlockSize = 0
        self.request = 0

        self.connect(self.socket, QtCore.SIGNAL("connected()"), self._send)
        self.connect(self.socket, QtCore.SIGNAL("readyRead()"), self._read)
        self.connect(
                        self.socket,
                        QtCore.SIGNAL("stateChanged (QAbstractSocket::SocketState)"),
                        self._stateChanged
                    )
        #self.connect(
                        #self.socket,
                        #QtCore.SIGNAL("disconnected()"),
                        #self.socket.close
                    #)
        self.connect(
                        self.socket,
                        QtCore.SIGNAL("error(QAbstractSocket::SocketError)"),
                        self._error
                    )

    def _stateChanged(self, state):
        print state

    def _send(self):
        '''Send the request to the remote client'''
        print "Connected, sending data"
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def _read(self):
        '''Read the server response'''
        print 'reading response'
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

            stream >> header >> data

            self.nextBlockSize = 0

    def _error(self):
        print "ERROR: %s" % self.socket.errorString()
        #self.socket.close()

    def send(self, msg, fields=[]):
        msg = QtCore.QString(msg)
        self.request = QtCore.QByteArray()
        stream = QtCore.QDataStream(self.request, QtCore.QIODevice.WriteOnly)
        stream.setVersion(STREAM_VERSION)
        stream.writeUInt16(0)

        stream << self.header << msg
        for field in fields:
            stream << QtCore.QString(field)

        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - UINT16)

        if self.socket.isOpen():
            print "Closing open socket"
            self.socket.close()

        print "Connecting to %s" % self.host
        self.socket.connectToHost(self.host, PORT)


class ServerThread(QtCore.QThread):
    # lock to prevent threads from attempting to access the same
    # resource
    lock = QtCore.QReadWriteLock()

    def __init__(self, socketIdent, parent=None):
        super(ServerThread, self).__init__(parent)
        self.socketIdent = socketIdent

    def run(self):
        socket = QtNetwork.QTcpSocket()

        if not socket.setSocketDescriptor(self.socketIdent):
            self.emit(QtCore.SIGNAL("error(int)"))
            print 'emit descriptor error'
            return

        # so long as we are connected, work with the socket
        while socket.state() == QtNetwork.QAbstractSocket.ConnectedState:
            print 'connected'
            nextBlockSize = 0
            stream = QtCore.QDataStream(socket)
            stream.setVersion(STREAM_VERSION)

            # ...but only so long as we have data work with
            if socket.waitForReadyRead(-1) and socket.bytesAvailable() >= UINT16:
                nextBlockSize = stream.readUInt16()

            else:
                print 'client request error'
                print socket.errorString()
                error = SocketReply(socket, "ERROR")
                error.send("Cannot read client request")
                return

            if socket.bytesAvailable() < nextBlockSize:
                readyWait = socket.waitForReadyRead(60000)
                bytesAvail = socket.bytesAvailable()

                if not readyWait or bytesAvail < nextBlockSize:
                    error = SocketReply(socket, "ERROR")
                    error.send("Cannot read client data")
                    return

            packet = QtCore.QString()
            stream >> packet
            print "packet", packet


class Server(QtNetwork.QTcpServer):
    def __init__(self, parent=None):
        super(Server, self).__init__(parent)

    def incomingConnection(self, socketIdent):
        '''
        Given the socket identifier thread the inoming
        connection and transfer all processing to ServerThread
        '''
        print "Incoming connection"
        thread = ServerThread(socketIdent, self)
        self.connect(
                        thread, QtCore.SIGNAL("finished()"),
                        thread, QtCore.SLOT("deleteLater()")
                    )
        thread.start()


class TestServer(QtCore.QObject):
    def __init__(self, parent=None):
        super(TestServer, self).__init__(parent)
        self.server = Server()


    def listen(self):
        listen = QtNetwork.QHostAddress("127.0.0.1")
        if not self.server.listen(listen, PORT):
            print self.server.errorString()

        print self.server.serverAddress().toString()
        print "server running"


class TestClient(QtCore.QObject):
    def __init__(self, parent=None):
        super(TestClient, self).__init__(parent)
        client = Client("127.0.0.1", "TEST")
        client.send("this is a test")

if __name__ == '__main__':
    import os
    import sys
    import signal
    signal.signal(signal.SIGINT, signal.SIG_DFL)

    mode = sys.argv[1]
    print "PID: %i" % os.getpid()

    app = QtCore.QCoreApplication(sys.argv)
    if mode.lower() == "client":
        client = TestClient()
        app.exec_()

    elif mode.lower() == "server":
        server = TestServer()
        server.listen()
        app.exec_()

    else:
        print "%s is not a valid option"
