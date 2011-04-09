'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008 (revised in July 2010)
PURPOSE: Group of classes dedicated to the collection and monitoring
of queue information.

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
import fnmatch

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
if PYFARM not in sys.path: sys.path.append(PYFARM)

import includes
from lib import logger, system, net

UNIT16         = 8
STREAM_VERSION = includes.STREAM_VERSION
logger         = logger.Logger()

class QueueClient(QtCore.QObject):
    '''
    Queue client used to connect to a queue server and
    exchange information.

    @param master: The address or hostname of a machine to send the packet to
    @type  master: C{str}
    @param port: The port to send the packet through
    @type  port: C{int}
    '''
    def __init__(self, master, port, parent=None):
        super(QueueClient, self).__init__(parent)
        self.master = master

    def addClient(self, new=True):
        '''Add the given client to the master'''
        netinfo = system.info.Network()
        if new:
            request = net.tcp.Request(
                                "CLIENT_NEW",
                                (netinfo.hostname(), netinfo.ip())
                            )
        else:
            request = net.tcp.Request(
                                "CLIENT_CONNECTED",
                                (netinfo.hostname(), netinfo.ip())
                             )
        self.connect(request, QtCore.SIGNAL("RESPONSE"), self.readResponse)
        request.send(self.master, self.port)

    def readResponse(self, response):
        '''Process the response from the server'''
        logger.netclient("Master responded: %s" % response)

class QueueServerThread(QtCore.QThread):
    '''
    Queue server thread spawned upon every incoming connection to
    prevent collisions.
    '''
    def __init__(self, socketId, main=None, parent=None):
        super(QueueServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent   = parent
        self.main     = main

    def run(self):
        logger.debug("Thread started")
        socket = QtNetwork.QTcpSocket()

        if not socket.setSocketDescriptor(self.socketId):
            self.emit(QtCore.SIGNAL("error(int)"), socket.error())
            return

        # while we are connected
        while socket.state() == QtNetwork.QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QtCore.QDataStream(socket) # create a data stream
            stream.setVersion(STREAM_VERSION)

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
            logger.netclient("Receieved Signal: %s" % action)

            if fnmatch.fnmatch(str(action), "CLIENT_*"):
                hostname = QtCore.QString()
                address  = QtCore.QString()
                stream >> hostname >> address

                if action == "CLIENT_NEW":
                    logger.netclient("Host: %s IP: %s" % (hostname, address))
                    self.main.addHost(hostname, address, mode="new")

                elif action == "CLIENT_CONNECTED":
                    msg = "%s's master is already %s" % (hostname, system.info.HOSTNAME)
                    self.main.updateConsole("client", msg, color='orange')
                    self.main.addHost(hostname, address, mode="refresh")

            self.sendReply(socket, action)
            socket.waitForDisconnected()

    def sendReply(self, socket, action):
        reply = QtCore.QByteArray()
        stream = QtCore.QDataStream(reply, QtCore.QIODevice.WriteOnly)
        stream.setVersion(STREAM_VERSION)
        stream.writeUInt16(0)
        stream << action
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UNIT16)
        socket.write(reply)


class QueueServer(QtNetwork.QTcpServer):
    '''
    Main queue server used to hold, gather, and update queue
    information on a network wide scale.  Examples include notifying
    the main gui of a finished frame, addition of a host to the network, and
    other similiar functions.  See QueueServerThread for the server logic.
    '''
    def __init__(self, main=None, parent=None):
        super(QueueServer, self).__init__(parent)
        self.main = main

        # server is running
        logger.netserver("Server Running")

    def incomingConnection(self, socketId):
        logger.netserver("Incoming Connection")
        self.thread = QueueServerThread(socketId, main=self.main, parent=self)
        self.connect(self.thread, QtCore.SIGNAL("finished()"), self.thread, QtCore.SLOT("deleteLater()"))
        self.thread.start()

# cleanup objects
del CWD, PYFARM
