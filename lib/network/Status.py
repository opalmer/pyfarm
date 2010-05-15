'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008
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
# From PyQt (Seperated into sections)
from PyQt4.QtCore import QThread, QObject, SIGNAL, SLOT, QString
from PyQt4.QtCore import QByteArray, QDataStream, QIODevice
from PyQt4.QtNetwork import QTcpServer, QTcpSocket
from PyQt4.QtNetwork import QAbstractSocket

# From PyFarm
from lib.ReadSettings import ParseXmlSettings

__MODULE__ = "lib.network.Status"
settings = ParseXmlSettings('./cfg/settings.xml')
UNIT16 = 8

class StatusServerThread(QThread):
    '''
    Status server thread spawned upon every incoming connection to
    prevent collisions.
    '''
    def __init__(self, dataJob, dataGeneral, socketId, parent=None):
        super(StatusServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent  = parent
        self.dataJob = dataJob
        self.dataGeneral = dataGeneral
        self.modName = 'StatusServerThread'

    def run(self):
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

            # prepare vars for input stream
            action = QString()
            options = QString()
            job = QString()
            subjob = QString()
            frame = QString()
            id = QString()
            pid = QString()
            host = QString()
            code = QString()

            stream >> action
            log("PyFarm :: %s :: Receieved the %s signal" % (self.modName, action), 'debug')
            # if the action is a preset
            if action in ("INIT", "newPID", "renderComplete", "renderFailed"):
                if action == "INIT":
                    stream >> options
                    self.parent.emit(SIGNAL('INIT'), str(options))
                elif action == "newPID":
                    stream >> job >> subjob >> frame >> id >> pid
                    self.dataJob[str(job)].data.frame.setPID(str(subjob), int(frame), str(id), str(pid))
                elif action == "renderComplete":
                    stream >> host >> job >> subjob >> frame >> id
                    log("PyFarm :: %s :: Frame complete - %s %i %s" % (self.modName, str(subjob), int(frame), str(id)), 'debug')
                    self.dataJob[str(job)].data.frame.setStatus(str(subjob), int(frame), str(id), 2)
                    self.dataJob[str(job)].data.frame.setEnd(str(subjob), int(frame), str(id))
                    self.dataGeneral.network.host.setStatus(str(host), 0)
                    self.parent.emit(SIGNAL("FRAME_COMPLETE"), (str(job), host, str(subjob), int(frame), str(id)))
                elif action == "renderFailed":
                    stream >> host >> job >> subjob >> frame >> id >> code
                    log("PyFarm :: %s :: Frame failed - %s %i %s" % (self.modName, str(subjob), int(frame), str(id)), 'error')
                    self.dataJob[str(job)].data.frame.setStatus(str(subjob), int(frame), str(id), 3)
                    self.dataJob[str(job)].data.frame.setEnd(str(subjob), int(frame), str(id))
                    self.dataGeneral.network.host.setStatus(str(host), 0)
                    self.parent.emit(SIGNAL("FRAME_FAILED"), (str(job), host, str(subjob), int(frame), str(id)))

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
    def __init__(self, dataJob, dataGeneral, logger, logLevels, parent=None):
        super(StatusServer, self).__init__(parent)
        # setup logging
        self.log = logger.moduleName("Status.StatusServer")
        self.log.debug("StatusServer loaded")
        self.logLevels = logLevels

        self.dataJob = dataJob
        self.dataGeneral = dataGeneral
        self.log.log(self.logLevels["DEBUG.NETWORK"], "Data structure assigned")

    def incomingConnection(self, socketId):
        log("PyFarm :: %s :: Incoming connection" % self.modName, 'debug')
        self.thread = StatusServerThread(self.dataJob, self.dataGeneral, socketId, self)
        self.connect(self.thread, SIGNAL("finished()"), self.thread, SLOT("deleteLater()"))
        self.thread.start()


class StatusClient(QObject):
    '''
    Status client used to connect to a status server and
    exchange information.

    INPUT:
        master (str) -- ip address of master to connect to
    '''
    def __init__(self, master, port=settings.netPort('status'), parent=None):
        super(StatusClient, self).__init__(parent)
        self.master = master
        self.modName = 'StatusClient'

        self.socket = QTcpSocket()
        self.port = port
        self.nextBlockSize = 0
        self.request = None

        try:
            self.connect(self.socket, SIGNAL("connected()"),self.sendRequest)
            self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
            self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
            self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)
        except TypeError:
            pass

    def updateMaster(self, cmd, data):
        '''
        Send an update to the master

        INPUT:
            cmd (str) -- command to run
            data (str) -- data to send
        '''
        self.issueRequest(QString(cmd), QString(data))

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
            log("PyFarm :: %s :: Already connected, closing socket" % self.modName, 'warning')
            self.socket.close()

        log("PyFarm :: %s :: Connecting to %s" % (self.modName, self.master), 'debug')
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
            log("PyFarm :: %s :: Already connected, closing socket" % self.modName, 'warning')
            self.socket.close()

        log("PyFarm :: %s :: Connecting to %s" % (self.modName, self.master), 'debug')
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
            log("PyFarm :: %s :: Already connected, closing socket" % self.modName, 'warning')
            self.socket.close()

        log("PyFarm :: %s :: Connecting to %s" % (self.modName, self.master), 'debug')
        self.socket.connectToHost(self.master, self.port)
        self.socket.waitForDisconnected(800)

    def sendRequest(self):
        log("PyFarm :: %s :: Sending signal to server" % self.modName, 'debug')
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        log("PyFarm :: %s :: Reading response" % self.modName, 'debug')
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

            if action in ("INIT", "UPDATE", "ERROR"):
                if action == "INIT":
                    log("PyFarm :: %s :: Master added this client to data" % (self.modName), 'debug')
                    self.emit(SIGNAL('MASTER_CONNECTED'))
                elif action == "UPDATE":
                    log("PyFarm :: %s :: Master updated status for this client" % (self.modName), 'debug')
                elif action == "ERROR":
                    log("PyFarm :: %s :: Master has error" % self.modName, 'error')
                    self.socket.close()

    def serverHasStopped(self):
        log("PyFarm :: %s :: %s: stopped" % (self.modName, self.master), 'warning')
        self.socket.close()

    def serverHasError(self):
        log("PyFarm :: %s :: %s: %s" % (self.modName, self.master, self.socket.errorString()), 'error')
        self.socket.close()
