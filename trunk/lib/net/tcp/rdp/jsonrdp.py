#!/usr/bin/env python26
#
# PURPOSE: To
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

from PyQt4 import QtNetwork, QtCore
from PyQt4.QtCore import Qt

MAC = "qt_mac_set_native_menubar" in dir()

PORT = 9407
UINT16 = 2
STREAM_VERSION = QtCore.QDataStream.Qt_4_2

class Packet(QtCore.QObject):
    '''Provides a common means of packing and sending data to a remote host'''
    def __init__(self, socket, server=None, port=None):
        super(Packet, self).__init__(parent)
        self.socket = socket
        self.server = server
        self.port = port
        self._reply = QtCore.QByteArray()
        self.stream = QtCore.QDataStream(self._reply, QtCore.QIODevice.WriteOnly)
        self.stream.setVersion(STREAM_VERSION)
        self.stream.writeUInt16(0)

    def _toQString(self, text):
        '''Conver the given text to a QString'''
        if type(text) != QtCore.QString:
            text = QtCore.QString(text)
        return text

    def _close(self):
        '''Close the packet'''
        self.stream.device().seek(0)
        self.stream.writeUInt16(self._reply.size() - UINT16)

    def _build(self, header, data, close=True):
        '''Open and prepare the packet'''
        self.stream << self._toQString(header) << self._toQString(data)

        if close:
            self._close()

    def _connect(self):
        '''
        Connect to the remove server, close the current connection if it
        is already open
        '''
        if self.socket.isOpen():
            print 'closing socket'
            self.socket.close()

        self.connectToHost(self.server, self.port)

    def _sendData(self):
        '''Send the packet data to the remote host'''
        print 'sending data'

    def _serverReponse(self):
        '''Read the reply from the remote server'''
        print 'reading server reply'

    def _serverDisconnected(self):
        '''Server has been disconnected, cleanup'''
        print 'server disconnected'

    def _serverError(self):
        '''Read and process an error from the server'''
        print 'server error'

    def setPort(self, port):
        '''Set the remove port to connect to'''
        self.port = port

    def setServer(self, server):
        '''Set the remote server to connect to'''
        self.server = server

    def reply(self, header, data):
        '''Send an error to the remote socket'''
        self._build(header, data)
        self.socket.write(self._reply)

    def send(self, header, data, server=None, port=None):
        '''Send the header and data to the remote host'''
        # set the server and port if given
        if server: self.setServer(server)
        if port: self.setPort(port)

        # setup signals and slots
        self.connect(
                        self.socket, QtCore.SIGNAL("connected()"),
                        self._sendData
                    )
        self.connect(
                        self.socket, QtCore.SIGNAL("readyRead()"),
                        self._serverReponse
                    )
        self.connect(
                        self.socket, QtCore.SIGNAL("disconnected()"),
                        self._serverDisconnected
                    )
        self.connect(
                        self.socket, QtCore.SIGNAL("error(QAbstractSocket::SocketError)"),
                        self._serverError
                    )

        # build the final stages of the packet and connect
        self._build(header, data)
        self._connect()


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

            # setup the incoming stream and reply packet
            packet = jsonrdp.Packet(socket)
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

class Server(QtNetwork.QTcpServer):
    def __init__(self, parent=None):
        super(Server, self).__init__(parent)

    def incomingConnection(self, socketIdent):
        print "incoming connection"
        thread = ServerThread(socketIdent, self)
        self.connect(
                        thread, QtCore.SIGNAL("finished()"),
                        thread, QtCore.SLOT("deleteLater()"))
        thread.start()