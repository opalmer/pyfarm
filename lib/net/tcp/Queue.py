'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008 (revised in July 2010)
PURPOSE: Group of classes dedicated to the collection and monitoring
of queue information.

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
from lib.Logger import Logger

# From PyQt
from PyQt4.QtCore import QThread, QObject, SIGNAL, SLOT, QString
from PyQt4.QtCore import QByteArray, QDataStream, QIODevice
from PyQt4.QtNetwork import QTcpServer, QTcpSocket, QAbstractSocket, QHostInfo

UNIT16 = 8

class NewRequest(QObject):
    '''
    Wrapper object to store basic request information

    VARIABLES:
        request (string) -- Name of request to send
        values (list|tuple) -- Values to send with request
    '''
    def __init__(self, request, values, parent=None):
        super(NewRequest, self).__init__(parent)
        self.log = Logger("Queue.NewRequest")
        self.socket = QTcpSocket()

        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

        self.data = QByteArray()
        self.stream = QDataStream(self.data, QIODevice.WriteOnly)
        self.stream.setVersion(QDataStream.Qt_4_2)
        self.stream.writeUInt16(0)

        # pack the values
        self.stream << QString(request.upper())
        for value in values:
            self.stream << QString(value)

        # prepare stream for transmission
        self.stream.device().seek(0)
        self.stream.writeUInt16(self.data.size() - UNIT16)

    def send(self, master, port, closeIfOpen=True, timeout=800):
        if self.socket.isOpen() and closeIfOpen:
            self.log.netclient("Connection is open, closing")
            self.socket.close()

        self.log.netclient("Connecting")
        self.socket.connectToHost(master, port)
        self.socket.waitForDisconnected(timeout)

    def sendRequest(self):
        self.log.netclient("sending request")
        self.nextBlockSize = 0
        self.socket.write(self.data)
        self.request = None

    def readResponse(self):
        self.log.debug("Reading response")
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < UNIT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break
            action = QString()
            options = QString()
            stream >> action
            self.nextBlockSize = 0

            self.log.netclient("Response action from master: %s" % action)
            self.emit(SIGNAL("RESPONSE"), action)
            self.socket.close()

    def serverHasStopped(self):
        self.log.error("Server has stopped")
        self.socket.close()

    def serverHasError(self):
        self.log.error("Server Error, %s" % self.socket.errorString())
        self.socket.close()


class QueueClient(QObject):
    '''
    Queue client used to connect to a queue server and
    exchange information.

    INPUT:
        master (str) -- ip address of master to connect to
    '''
    def __init__(self, master, port=65501, parent=None):
        super(QueueClient, self).__init__(parent)
        self.master = master
        self.port = port
        self.log = Logger("Queue.Client")

#####################
# NEW REQUEST FORMAT
#####################
    def readResponse(self, response):
        '''Process the response from the server'''
        self.log.netclient("Master responded: %s" % response)
        # processing begins here

    def addClient(self, hostname, ip):
        '''Add the given client to the master'''
        hostname = str(QHostInfo.localHostName())
        self.log.fixme("Software, system, and network specs not implimented")
        request = NewRequest("CLIENT_NEW", ("octo", "10.56.1.2", "4096"))
        self.connect(request, SIGNAL("RESPONSE"), self.readResponse)
        request.send(self.master, self.port)


class QueueServerThread(QThread):
    '''
    Queue server thread spawned upon every incoming connection to
    prevent collisions.
    '''
    def __init__(self, socketId, parent=None):
        super(QueueServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent  = parent
        self.log = Logger("Queue.ServerThread")

    def run(self):
        self.log.debug("Thread started")
        socket = QTcpSocket()

        if not socket.setSocketDescriptor(self.socketId):
            self.emit(SIGNAL("error(int)"), socket.error())
            return

        # while we are connected
        while socket.state() == QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QDataStream(socket) # create a data stream
            stream.setVersion(QDataStream.Qt_4_2)

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

#            # prepare vars for input stream
            action = QString()
#            job = QString()
#            subjob = QString()
#            frame = QString()
#            id = QString()
#            pid = QString()
#            host = QString()
#            code = QString()
#
            stream >> action
            self.log.netclient("Receieved signal: %s" % action)
            if action == "CLIENT_NEW":
                hostname = QString()
                address = QString()
                ram = QString()
                stream >> hostname >> address >> ram
                self.log.netclient("Hostname: %s" % hostname)
                self.log.netclient("Addrss: %s" % address)
                self.log.netclient("Ram: %s" % ram)
#            if action in ("INIT", "newPID", "renderComplete", "renderFailed"):
#                if action == "INIT":
#                    stream >> options
#                    self.parent.emit(SIGNAL('INIT'), str(options))
#                elif action == "newPID":
#                    stream >> job >> subjob >> frame >> id >> pid
#                    self.dataJob[str(job)].data.frame.setPID(str(subjob), int(frame), str(id), str(pid))
#                elif action == "renderComplete":
#                    stream >> host >> job >> subjob >> frame >> id
#                    log("PyFarm :: %s :: Frame complete - %s %i %s" % (self.modName, str(subjob), int(frame), str(id)), 'debug')
#                    self.dataJob[str(job)].data.frame.setQueue(str(subjob), int(frame), str(id), 2)
#                    self.dataJob[str(job)].data.frame.setEnd(str(subjob), int(frame), str(id))
#                    self.dataGeneral.network.host.setQueue(str(host), 0)
#                    self.parent.emit(SIGNAL("FRAME_COMPLETE"), (str(job), host, str(subjob), int(frame), str(id)))
#                elif action == "renderFailed":
#                    stream >> host >> job >> subjob >> frame >> id >> code
#                    log("PyFarm :: %s :: Frame failed - %s %i %s" % (self.modName, str(subjob), int(frame), str(id)), 'error')
#                    self.dataJob[str(job)].data.frame.setQueue(str(subjob), int(frame), str(id), 3)
#                    self.dataJob[str(job)].data.frame.setEnd(str(subjob), int(frame), str(id))
#                    self.dataGeneral.network.host.setQueue(str(host), 0)
#                    self.parent.emit(SIGNAL("FRAME_FAILED"), (str(job), host, str(subjob), int(frame), str(id)))

            # final send a back the original host
            self.log.fixme("NOT SENDING REPLY!")
#            self.sendReply(socket, action, options)
            socket.waitForDisconnected()

    def sendReply(self, socket, action, options):
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << options
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UNIT16)
        socket.write(reply)


class QueueServer(QTcpServer):
    '''
    Main queue server used to hold, gather, and update queue
    information on a network wide scale.  Examples include notifying
    the main gui of a finished frame, addition of a host to the network, and
    other similiar functions.  See QueueServerThread for the server logic.
    '''
    def __init__(self, parent=None):
        super(QueueServer, self).__init__(parent)
        self.log = Logger("Queue.Server")
        self.log.netserver("Server Running")

    def incomingConnection(self, socketId):
        self.log.netserver("Incoming Connection")
        self.thread = QueueServerThread(socketId, self)
        self.connect(self.thread, SIGNAL("finished()"), self.thread, SLOT("deleteLater()"))
        self.thread.start()