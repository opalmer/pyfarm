'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 2008 (revised in August 2010)
PURPOSE: Administration server to provide vital functions to the client such
as proper shutdown procedures, service restarts, etc.

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

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import Logger, net

UNIT16         = 8
STREAM_VERSION = net.dataStream()

class AdminClient(QtCore.QObject):
    '''
    Client to interface with remove administration server.

    INPUT:
        master (str) -- ip address of master to connect to
    '''
    def __init__(self, master, port=65504, parent=None):
        super(AdminClient, self).__init__(parent)
        self.master = master
        self.port   = port
        self.log    = Logger.Logger("Admin.Client")

#####################
# NEW REQUEST FORMAT
#####################
    def readResponse(self, response):
        '''Process the response from the server'''
        self.log.netclient("Master responded: %s" % response)
        # processing begins here

    def addClient(self, hostname, ip):
        '''Add the given client to the master'''
        hostname = str(QtNetwork.QHostInfo.localHostName())
        self.log.fixme("Software, system, and network specs not implimented")
        request = net.tcp.Request("CLIENT_SHUTDOWN", ("octo", "10.56.1.2", "4096"))
        self.connect(request, QtCore.SIGNAL("RESPONSE"), self.readResponse)
        request.send(self.master, self.port)


class AdminServerThread(QtCore.QThread):
    '''
    Admin server thread spawned upon every incoming connection to
    prevent collisions.
    '''
    def __init__(self, socketId, parent=None):
        super(AdminServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent   = parent
        self.log      = Logger.Logger("Admin.ServerThread")

    def run(self):
        self.log.debug("Thread started")
        socket = QtNetwork.QtNetwork.QAbstractSocket()

        if not socket.setSocketDescriptor(self.socketId):
            self.emit(QtCore.SIGNAL("error(int)"), socket.error())
            return

        # while we are connected
        while socket.state() == QtNetwork.QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QtCore.QDataStream(socket) # create a data stream
            stream.setVersion(QtCore.QDataStream.Qt_4_2)

            while True:
                # wait for the stream to be ready to read
                socket.waitForReadyRead(-1)

                # load next block size with the total size of the stream
                if socket.bytesAvailable() >= UNIT16:
                    nextBlockSize = stream.readUInt16()
                    break

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                        break

            # prepare vars for input stream
            action = QtCore.QString()
            stream >> action

            self.log.netclient("Receieved QtCore.SIGNAL: %s" % action)
            if action == "CLIENT_NEW":
                hostname = QtCore.QString()
                address = QtCore.QString()
                ram = QtCore.QString()
                stream >> hostname >> address >> ram
                self.log.netclient("Hostname: %s" % hostname)
                self.log.netclient("Addrss: %s" % address)
                self.log.netclient("Ram: %s" % ram)

            # final send a back the original host
            self.log.fixme("NOT SENDING REPLY!")
            self.sendReply(socket, action, options)
            socket.waitForDisconnected()

    def sendReply(self, socket, action, options):
        reply = QtCore.QByteArray()
        stream = QtCore.QDataStream(reply, QtCore.QIODevice.WriteOnly)
        stream.setVersion(QtCore.QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << options
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UNIT16)
        socket.write(reply)


class AdminServer(QtNetwork.QTcpServer):
    '''
    Main admin server used to control each individual client.  Controls include client
    restart, service/software discovery, etc.
    '''
    def __init__(self, main=None, parent=None):
        super(AdminServer, self).__init__(parent)
        self.main = main
        self.log  = Logger.Logger("Admin.Server")
        self.log.netserver("Server Running")

    def incomingConnection(self, socketId):
        self.log.netserver("Incoming Connection")
        self.thread = AdminServerThread(socketId, self)
        self.connect(self.thread, QtCore.SIGNAL("finished()"), self.thread, QtCore.SLOT("deleteLater()"))
        self.thread.start()
