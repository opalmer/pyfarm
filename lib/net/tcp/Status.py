'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008 (revised in July 2010)
PURPOSE: Group of classes dedicated to the collection and monitoring
of status information.

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

# From PyQt (Seperated into sections)
from PyQt4.QtCore import QThread, QObject, SIGNAL, SLOT, QString
from PyQt4.QtCore import QByteArray, QDataStream, QIODevice
from PyQt4.QtNetwork import QTcpServer, QTcpSocket, QAbstractSocket

UNIT16 = 8

class NewRequest(QObject):
    '''
    Wrapper object to store basic request information

    VARIABLES:
        request (string) -- Name of request to send
        values (list|tuple) -- Values to send with request
    '''
    def __init__(self, socket, request, values, parent=None):
        super(NewRequest, self).__init__(parent)
        # initial setup
        self.log = Logger("Status.Client")
        self.socket = socket
        self.connect(self.socket, SIGNAL("connected()"),self.sendRequest)

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
            self.log.netclient("Connection is open,  closing")
            self.socket.close()

        self.log.netclient("Connecting")
        self.socket.connectToHost(master, port)
        self.socket.waitForDisconnected(timeout)

    def sendRequest(self):
        self.log.netclient("sending request")


class StatusClient(QObject):
    '''
    Status client used to connect to a status server and
    exchange information.

    INPUT:
        master (str) -- ip address of master to connect to
    '''
    def __init__(self, master, port=65501, parent=None):
        super(StatusClient, self).__init__(parent)
        self.master = master
        self.log = Logger("Status.StatausClient")
        self.log.fixme("HARDCODED PORT KEYWORD")

        self.socket = QTcpSocket()
        self.port = port
        self.nextBlockSize = 0
        self.request = None
        self.connect(self.socket, SIGNAL("connected()"),self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)
        self.connect(self.socket, SIGNAL("stateChanged(QAbstractSocket::SocketState)"), self.newState)

    def newState(self, state):
        print state

    def updateMaster(self, cmd, data):
        '''
        Send an update to the master

        INPUT:
            cmd (str) -- command to run
            data (str) -- data to send
        '''
        self.issueRequest(QString(cmd), QString(data))

    ########################
    ## New way of writing to tcp below
    ########################
    ########################
    ## New way of writing to tcp below
    ########################
    ########################
    ## New way of writing to tcp below
    ########################
    def pingMaster(self):
        '''
        Ping the master and inform it on basic host information.
        Only send more information if requested
        '''
        request = NewRequest(self.socket, 'HEARTBEAT', ('hello', 'world', 'I', 'am', 'alive'), self)
        request.send(self.master, self.port)

    def sendPID(self, job, subjob, frame, id, pid):
        '''Send the process id to the status server'''
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # create the packet
        stream << QString("newPID")
        for var in (job, subjob, frame, id, pid):
            stream << QString(var)

        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - UNIT16)

        if self.socket.isOpen():
            self.log.warning("Already connected,  closing socket")
            self.socket.close()

        self.log.netclient("Connecting...")
        self.socket.connectToHost(self.master, self.port)
        self.socket.waitForDisconnected(800)

    def renderComplete(self, host, job, subjob, frame, id):
        '''Send a render complete packet to the status server'''
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # create the packet
        stream << QString("renderComplete")
        for var in (host, job, subjob, frame, id):
            stream << QString(var)

        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - UNIT16)

        if self.socket.isOpen():
            log("PyFarm :: %s :: Already connected, closing socket" % self.modName, 'warning')
            self.socket.close()

        self.log.debug("Connecting...")
        log("PyFarm :: %s :: Connecting to %s" % (self.modName, self.master), 'debug')
        self.socket.connectToHost(self.master, self.port)
        self.socket.waitForDisconnected(800)

    def renderFailed(self, host, job, subjob, frame, id, code):
        '''Inform the staus of a failed render'''
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # create the packet
        stream << QString("renderFailed")
        for var in (host, job, subjob, frame, id, code):
            stream << QString(var)

        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - UNIT16)

        if self.socket.isOpen():
            self.log.warning("Already connected,  closing socket")
            self.socket.close()

        self.log.debug("Connecting...")
        self.socket.connectToHost(self.master, self.port)
        self.socket.waitForDisconnected(800)

    def issueRequest(self, action, options=None):
        '''Issue the given request the remove host'''
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        if options:
            stream << action << options
        else:
            stream << action
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - UNIT16)

        if self.socket.isOpen():
            self.log.warning("Already connected!")
            self.socket.close()

        self.log.debug("Connecting...")
        self.socket.connectToHost(self.master, self.port)
        self.socket.waitForDisconnected(800)

    def sendRequest(self):
        self.log.debug("Sending request to server")
        self.nextBlockSize = 0
        self.socket.write(self.request)
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

            if action == "INIT":
                self.log.netclient("Master added client to database")
                self.emit(SIGNAL('MASTER_CONNECTED'))
            elif action == "UPDATE":
                self.log.netclient("Master updated status for this client")
            elif action == "ERROR":
                self.log.error("Master has error!")
                self.socket.close()

    def serverHasStopped(self):
        self.log.error("Server has stopped")
        self.socket.close()

    def serverHasError(self):
        self.log.error("Server Error, %s" % self.socket.errorString())
        self.socket.close()


class StatusServerThread(QThread):
    '''
    Status server thread spawned upon every incoming connection to
    prevent collisions.
    '''
    def __init__(self, socketId, parent=None):
        super(StatusServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent  = parent
        self.log = Logger("Status.ServerThread")

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
#            action = QString()
#            options = QString()
#            job = QString()
#            subjob = QString()
#            frame = QString()
#            id = QString()
#            pid = QString()
#            host = QString()
#            code = QString()
#
#            stream >> action
#            log("PyFarm :: %s :: Receieved the %s signal" % (self.modName, action), 'debug')
#            # if the action is a preset
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
#                    self.dataJob[str(job)].data.frame.setStatus(str(subjob), int(frame), str(id), 2)
#                    self.dataJob[str(job)].data.frame.setEnd(str(subjob), int(frame), str(id))
#                    self.dataGeneral.network.host.setStatus(str(host), 0)
#                    self.parent.emit(SIGNAL("FRAME_COMPLETE"), (str(job), host, str(subjob), int(frame), str(id)))
#                elif action == "renderFailed":
#                    stream >> host >> job >> subjob >> frame >> id >> code
#                    log("PyFarm :: %s :: Frame failed - %s %i %s" % (self.modName, str(subjob), int(frame), str(id)), 'error')
#                    self.dataJob[str(job)].data.frame.setStatus(str(subjob), int(frame), str(id), 3)
#                    self.dataJob[str(job)].data.frame.setEnd(str(subjob), int(frame), str(id))
#                    self.dataGeneral.network.host.setStatus(str(host), 0)
#                    self.parent.emit(SIGNAL("FRAME_FAILED"), (str(job), host, str(subjob), int(frame), str(id)))

            # final send a back the original host
            self.sendReply(socket, action, options)
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


class StatusServer(QTcpServer):
    '''
    Main status server used to hold, gather, and update status
    information on a network wide scale.  Examples include notifying
    the main gui of a finished frame, addition of a host to the network, and
    other similiar functions.  See StatusServerThread for the server logic.
    '''
    def __init__(self, parent=None):
        super(StatusServer, self).__init__(parent)
        self.log = Logger("Status.Server")
        self.log.netserver("Server Running")

    def incomingConnection(self, socketId):
        self.log.netserver("Incoming Connection")
        self.thread = StatusServerThread(socketId, self)
        self.connect(self.thread, SIGNAL("finished()"), self.thread, SLOT("deleteLater()"))
        self.thread.start()
