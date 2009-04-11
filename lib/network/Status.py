'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008
PURPOSE: Group of classes dedicated to the collection and monitoring
of status information.

    This file is part of PyFarm.
    Copyright (C) 2008-2009 Oliver Palmer

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
# From Python
from os import getcwd

# From PyQt (Seperated into sections)
from PyQt4.QtCore import QThread, QObject, SIGNAL, SLOT, QString
from PyQt4.QtCore import QByteArray, QDataStream, QIODevice
from PyQt4.QtNetwork import QTcpServer, QTcpSocket
from PyQt4.QtNetwork import QAbstractSocket

# From PyFarm
from lib.ReadSettings import ParseXmlSettings

settings = ParseXmlSettings('%s/settings.xml' % getcwd())

class StatusServerThread(QThread):
    '''
    Status server thread spawned upon every incoming connection to
    prevent collisions.
    '''
    def __init__(self, socketId, parent=None):
        super(StatusServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent  = parent
        self.modName = 'StatusServerThread'

    def run(self):
        socket = QTcpSocket()
        print "PyFarm :: %s :: Running server" % self.modName

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
                if socket.bytesAvailable() >= settings.netGeneral('unit16'):
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
            print "PyFarm :: %s :: Unpacking packet" % self.modName
            stream >> action
            print "PyFarm :: %s :: Receieved the %s signal" % (self.modName, action)

            # if the action is a preset
            if action in ("INIT", "UPDATE"):
                stream >> options
                if action == "INIT":
                    self.parent.emitSignal('INIT', str(options))
                elif action == "UPDATE":
                    print "UPDATE FROM CLIENT"

                # final send a back the original host
                self.sendReply(socket, action, options)
            else:
                    self.sendError(socket, "%s is not a valid option" % action)
            socket.waitForDisconnected()

    def sendReply(self, socket, action, options):
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << options
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - settings.netGeneral('unit16'))
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
        self.modName = 'StatusServer'

    def incomingConnection(self, socketId):
        print "PyFarm :: %s :: Incoming connection" % self.modName
        self.thread = StatusServerThread(socketId, self)
        self.connect(self.thread, SIGNAL("finished()"), self.thread, SLOT("deleteLater()"))
        self.thread.start()

    def emitSignal(self, sig, data):
        '''
        Emit a signal back to the main program

        INPUT:
            sig (str) -- name of signal to emit
            data (str) -- data to emit
        '''
        self.emit(SIGNAL(sig), data)


class StatusClient(QObject):
    '''
    Status client used to connect to a status server and
    exchange information.

    INPUT:
        master (str) -- ip address of master to connect to
    '''
    def __init__(self, master, port, parent=None):
        super(StatusClient, self).__init__(parent)
        self.master = master
        self.modName = 'StatusClient'

        self.socket = QTcpSocket()
        self.port = port
        self.nextBlockSize = 0
        self.request = None

        self.connect(self.socket, SIGNAL("connected()"),self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def updateMaster(self, cmd, data):
        '''
        Send an update to the master

        INPUT:
            cmd (str) -- command to run
            data (str) -- data to send
        '''
        self.issueRequest(QString(cmd), QString(data))

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
        stream.writeUInt16(self.request.size() - settings.netGeneral('unit16'))

        if self.socket.isOpen():
            print "PyFarm :: %s :: Already connected, closing socket" % self.modName
            self.socket.close()

        print "PyFarm :: %s :: Connecting to %s" % (self.modName, self.master)
        self.socket.connectToHost(self.master, self.port)
        self.socket.waitForDisconnected(800)

    def sendRequest(self):
        print "PyFarm :: %s :: Sending signal to server" % self.modName
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        print "PyFarm :: %s :: Reading response" % self.modName
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < settings.netGeneral('unit16'):
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
                    print "PyFarm :: %s :: Master added this client to data" % (self.modName)
                    self.emit(SIGNAL('MASTER_CONNECTED'))
                elif action == "UPDATE":
                    print "PyFarm :: %s :: Master updated status for this client" % (self.modName)
                elif action == "ERROR":
                    print "PyFarm :: %s :: Master has error" % self.modName
                    self.socket.close()

    def serverHasStopped(self):
        print "PyFarm :: %s :: %s: stopped" % (self.modName, self.master)
        self.socket.close()

    def serverHasError(self, error):
        print "PyFarm :: %s :: %s: %s" % (self.modName, self.master, self.socket.errorString())
        self.socket.close()
