'''
HOMEPAGE: www.pyfarm.net
INITIAL: Aug. 26 2010
PURPOSE: Template servers, threads, and packet construction

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys

from PyQt4 import QtNetwork, QtCore

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import logger

UNIT16         = 8
QT_VERSION     = QtCore.QT_VERSION_STR.split('.')
STREAM_VERSION = eval('QtCore.QDataStream.Qt_%s_%s' %(QT_VERSION[0], QT_VERSION[1]))

class Request(QtCore.QObject):
    '''
    Wrapper object to store basic request information

    VARIABLES:
        request (string) -- Name of request to send
        values (list|tuple) -- Values to send with request
        logName (string) -- Name to set logger to
    '''
    def __init__(self, request, values, logName="Base.Request", parent=None):
        super(Request, self).__init__(parent)
        self.log    = logger.Logger(logName)
        self.socket = QtNetwork.QTcpSocket()

        self.connect(
                         self.socket,
                         QtCore.SIGNAL("connected()"),
                         self.sendRequest
                     )

        self.connect(
                         self.socket,
                         QtCore.SIGNAL("readyRead()"),
                         self.readResponse
                     )

        self.connect(
                         self.socket,
                         QtCore.SIGNAL("disconnected()"),
                         self.serverHasStopped
                     )

        self.connect(
                         self.socket,
                         QtCore.SIGNAL("error(QAbstractSocket::SocketError)"),
                         self.serverHasError
                     )

        # initial data stream configuration
        self.data = QtCore.QByteArray()
        self.stream = QtCore.QDataStream(self.data, QtCore.QIODevice.WriteOnly)
        self.stream.setVersion(STREAM_VERSION)
        self.stream.writeUInt16(0)

        # pack all values into the data stream
        self.stream << QtCore.QString(request.upper())
        for value in values:
            self.stream << QtCore.QString(value)

        # prepare stream for transmission
        self.stream.device().seek(0)
        self.stream.writeUInt16(self.data.size() - UNIT16)

    def send(self, master, port, closeIfOpen=True, timeout=800):
        '''Connect to, pack, and prepare to send the request'''
        if self.socket.isOpen() and closeIfOpen:
            self.log.netclient("Connection is open, closing")
            self.socket.close()

        self.log.netclient("Connecting")
        self.socket.connectToHost(master, port)
        self.socket.waitForDisconnected(timeout)

    def sendRequest(self):
        '''Write the request to the connected socket'''
        self.log.netclient("Sending request")
        self.nextBlockSize = 0
        self.socket.write(self.data)
        self.request = None

    def readResponse(self):
        '''Read the response from the server'''
        self.log.debug("Reading response")
        stream = QtCore.QDataStream(self.socket)
        stream.setVersion(STREAM_VERSION)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < UNIT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break
            action = QtCore.QString()
            options = QtCore.QString()
            stream >> action
            self.nextBlockSize = 0

            self.log.netclient("Response action from master: %s" % action)
            self.emit(QtCore.SIGNAL("RESPONSE"), action)
            self.socket.close()

    def serverHasStopped(self):
        '''Log and close the socket when server stops'''
        self.log.error("Server has stopped")
        self.socket.close()

    def serverHasError(self):
        '''Log and close the socket on error'''
        self.log.error("Server Error, %s" % self.socket.errorString())
        self.socket.close()
