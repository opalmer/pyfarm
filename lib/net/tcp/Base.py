'''
HOMEPAGE: www.pyfarm.net
INITIAL: Aug. 26 2010
PURPOSE: Template servers, threads, and packet construction

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
from PyQt4 import QtNetwork, QtCore

UNIT16  = 8
STREAM_VERSION = QtCore.QDataStream.Qt_4_6

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
        self.log = Logger(logName)
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


class ServerThread(QtCore.QThread):
    '''
    Server thread spawned upon every incoming connection to
    prevent blocking.
    '''
    def __init__(self, socketId, parent=None):
        super(ServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent  = parent
        self.log = Logger("Queue.ServerThread")

    def run(self):
        self.log.debug("Thread started")
        socket = QtNetwork.QTcpSocket()

        if not socket.setSocketDescriptor(self.socketId):
            self.emit(
                      QtCore.SIGNAL("error(int)"),
                      socket.error()
                      )
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

#            # prepare vars for input stream
            action = QtCore.QString()
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

# TODO: Port below action to subclass
#            if action == "CLIENT_NEW":
#                hostname = QtCore.QString()
#                address = QtCore.QString()
#                ram = QtCore.QString()
#                stream >> hostname >> address >> ram
#                self.log.netclient("Hostname: %s" % hostname)
#                self.log.netclient("Addrss: %s" % address)
#                self.log.netclient("Ram: %s" % ram)
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
        reply = QtCore.QByteArray()
        stream = QtCore.QDataStream(reply, QtCore.QIODevice.WriteOnly)
        stream.setVersion(STREAM_VERSION)
        stream.writeUInt16(0)
# TODO: Port below action to subclass
        stream << action << options
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UNIT16)
        socket.write(reply)


class Server(QtNetwork.QTcpServer):
    '''Main TCP server to handle incoming connections'''
    def __init__(self, serverName="Base.Server", theadName="Base.ServerThread", parent=None):
        super(Server, self).__init__(parent)
        self.threadName = theadName
        self.log = Logger(serverName)
        self.log.netserver("Server Running")

    def incomingConnection(self, socketId):
        self.log.netserver("Incoming Connection")
        self.thread = ServerThread(socketId, self.threadName, self)
        self.connect(self.thread, SIGNAL("finished()"), self.thread, SLOT("deleteLater()"))
        self.thread.start()
