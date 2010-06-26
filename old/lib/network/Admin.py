'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 11 2009
PURPOSE: Network modules used in the administration of remote clients

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
# From PyQt
from PyQt4.QtCore import QThread, QString, QObject
from PyQt4.QtCore import SIGNAL, QDataStream, QByteArray, QIODevice, SLOT
from PyQt4.QtNetwork import QAbstractSocket, QTcpSocket, QTcpServer

# From PyFarm
from lib.Logger import Logger
from lib.ReadSettings import ParseXmlSettings

__MODULE__ = "lib.network.Admin"
__LOGLEVEL__ = 4

settings = ParseXmlSettings('./cfg/settings.xml',  'cmd',  skipSoftware=False)

UNIT16 = 8

class AdminServerThread(QThread):
    '''Admin server thread spawned by AdminServer'''
    def __init__(self, socketId, parent):
        super(AdminServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent = parent
        self.modName = 'Network.AdminServerThread'

    def run(self):
        '''
        The main function of the thread as called by
        AdminServer @ AdminServerThread.start()
        '''
        socket = QTcpSocket()
        log("PyFarm :: %s :: Running server thread" % self.modName, 'debug')

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
            log("PyFarm :: %s :: Unpacking packet" % self.modName, 'debug')
            stream >> action
            log("PyFarm :: %s :: Receieved the %s signal" % (self.modName, action), 'debug')

            # if the action is a preset
            if action in ("SHUTDOWN", "RESTART", "SYSINFO"):
                stream >> options
                if action == "SHUTDOWN":
                    self.emit(SIGNAL("SHUTDOWN"))
                elif action == "RESTART":
                    self.emit(SIGNAL("RESTART"))
                elif action == "SYSINFO":
                    self.sendSysInfo(socket)

                # final send a back the original host
                self.sendReply(socket, action, options)

            # unless the user requested an action that does
            #  not exist
            else:
                    self.sendError(socket, "%s is not a valid option" % action)
            socket.waitForDisconnected()

    def sendError(self, socket, msg):
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << QString("ERROR") << QString(msg)
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UNIT16)
        socket.write(reply)

    def sendReply(self, socket, action, options):
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << options
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UNIT16)
        socket.write(reply)

    def sendSysInfo(self, socket):
        # gather required info
        systemInfo = System().os()
        os = systemInfo[0]
        arch = systemInfo[1]

        # create the packet
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # pack the information
        output = 'ip::%s,hostname::%s,os::%s,arch::%s%s' \
        % (self.parent.serverAddress().toString(), QHostInfo.localHostName(), os, arch, settings.software())
        stream << QString("SYSINFO") << QString(output)
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UNIT16)

        # send the reply
        socket.write(reply)


class AdminServer(QTcpServer):
    '''
    Primary Admin Server
    Takes incomingConnection and starts a thread
    to handle the connection, keeps the connection from blocking.
    '''
    def __init__(self, parent=None):
        super(AdminServer, self).__init__(parent)
        self.modName = 'Network.AdminServer'

    def incomingConnection(self, socketId):
        log("PyFarm :: %s :: Incoming connection" % self.modName, 'debug')
        self.serverThread = AdminServerThread(socketId, self)
        self.connect(self.serverThread, SIGNAL("finished()"), self.serverThread, SLOT("deleteLater()"))
        self.connect(self.serverThread, SIGNAL("SHUTDOWN"), self.emitShutdown)
        self.connect(self.serverThread, SIGNAL("RESTART"), self.emitRestart)
        self.serverThread.start()

    def shutdown(self):
        '''Shutdown the server and try to terminate all threads'''
        try:
            self.serverThread.quit()
            self.serverThread.wait()
        except AttributeError:
            log("PyFarm :: %s :: No threads to terminate" % self.modName, 'warning')
        finally:
            self.close()

    def emitShutdown(self):
        '''
        After receiving the shutdown signal from the thread,
        emit SHUTDOWN to the parent.
        '''
        log("PyFarm :: %s :: Broadcasting shutdown signal" % self.modName, 'warning')
        self.emit(SIGNAL("SHUTDOWN"))

    def emitRestart(self):
        '''
        After receiving the restart signal from the thread,
        emit RESTART to the parent.
        '''
        log("PyFarm :: %s :: Broadcasting restart signal" % self.modName, 'warning')
        self.emit(SIGNAL("RESTART"))


class AdminClient(QObject):
    def __init__(self, client, port=settings.netPort('admin'), parent=None):
        super(AdminClient, self).__init__(parent)
        self.modName = 'Network.AdminClient'

        self.socket = QTcpSocket()
        self.client = client
        self.port = port
        self.nextBlockSize = 0
        self.request = None

        self.connect(self.socket, SIGNAL("connected()"),self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def shutdown(self):
        '''Shutdown the client'''
        self.issueRequest(QString("SHUTDOWN"), QString("all processes"))

    def restart(self):
        '''Restart the client'''
        self.issueRequest(QString("RESTART"), QString("kill renders"))

    def systemInfo(self):
        '''Return info about the remote sytem'''
        self.issueRequest(QString("SYSINFO"))

    def issueRequest(self, action, options=None):
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

        log("PyFarm :: %s :: Connecting to %s" % (self.modName, self.client), 'debug')
        self.socket.connectToHost(self.client, self.port)
        self.socket.waitForDisconnected(800) # wait to finish transmission to each admin server

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

            if action in ("SHUTDOWN", "RESTART", "SYSINFO"):
                if action == "SHUTDOWN":
                    log("PyFarm :: %s :: %s is shutting down" % (self.modName, self.client), 'warning')
                elif action == "RESTART":
                    log("PyFarm :: %s :: %s is restarting" % (self.modName, self.client), 'warning')
                elif action == "SYSINFO":
                    stream >> options
                    self.socket.close()
                    self.emit(SIGNAL("newSysInfo"), options)

    def serverHasStopped(self):
        log("PyFarm :: %s :: %s has stopped" % (self.modName, self.client), 'warning')
        self.socket.close()

    def serverHasError(self, error):
        log("PyFarm :: %s :: %s has error: %s" % (self.modName, self.client, self.socket.errorString()), 'error')
        self.socket.close()
