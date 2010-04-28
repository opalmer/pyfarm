'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 11 2009
PURPOSE: Network modules used to hand queue commands

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
from PyQt4.QtNetwork import QTcpSocket, QTcpServer, QAbstractSocket
from PyQt4.QtCore import QThread, QProcess
from PyQt4.QtCore import SIGNAL, SLOT, QByteArray, QDataStream, QString, QIODevice

# From PyFarm
import lib.Logger as logger
from lib.network.Status import StatusClient
from lib.network.JobLogging import UdpLoggerClient
from lib.ReadSettings import ParseXmlSettings

__MODULE__ = "lib.network.Que"

settings = ParseXmlSettings('./cfg/settings.xml', skipSoftware=False)

UNIT16 = 8

class QueSlaveServerThread(QThread):
    '''Wait for the master to ready the que, then receive the first command'''
    def __init__(self, socketId, master, parent=None):
        super(QueSlaveServerThread, self).__init__(parent)
        self.master = master
        self.socketId = socketId
        self.parent = parent
        self.modName = 'Que.QueSlaveServerThread'

    def run(self):
        '''Run the server thread and process the requested data'''
        self.socket = QTcpSocket()
        log("PyFarm :: %s :: Starting thread" % self.modName, 'debug')

        if not self.socket.setSocketDescriptor(self.socketId):
            self.emit(SIGNAL("error(int)"), self.socket.error())
            return

        # while we are connected
        while self.socket.state() == QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QDataStream(self.socket) # create a data stream
            stream.setVersion(QDataStream.Qt_4_2)

            while True:
                # wait for the stream to be ready to read
                self.socket.waitForReadyRead(-1)

                # load next block size with the total size of the stream
                if self.socket.bytesAvailable() >= UNIT16:
                    nextBlockSize = stream.readUInt16()
                    break

            if self.socket.bytesAvailable() < nextBlockSize:
                while True:
                    self.socket.waitForReadyRead(-1)
                    if self.socket.bytesAvailable() >= nextBlockSize:
                        break

            self.host = QString()
            self.job = QString()
            self.subjob = QString()
            self.frame = QString()
            self.id = QString()
            self.software = QString()
            self.arguments = QString()

            log("PyFarm :: %s :: Unpacking the command" % self.modName, 'debug')
            stream >> self.host >> self.job >> self.subjob >> self.frame >> self.id >> self.software >> self.arguments
            self.command = QString(settings.command(str(self.software)))

            self.lineOut = "%s::%s::%s::%s::" % \
                       (self.job, self.subjob, self.frame, self.id)

            self.process = QProcess(self)
            self.stdout = UdpLoggerClient(self.master, parent=self)
            self.processStartedCalled = 0
            self.connect(self.process, SIGNAL("started()"), self.processStarted)
            self.connect(self.process, SIGNAL("readyReadStandardOutput()"), self.processStdOut)
            self.connect(self.process, SIGNAL("readyReadStandardError()"), self.processStdErr)
            self.process.start("%s%s" % (self.command, self.arguments))

            self.processID = str(self.process.pid())
            self.process.waitForFinished(-1) # wait for the process to finish before continuing
            self.processFinished()
            self.socket.waitForDisconnected()

    def processFinished(self):
        '''To be run when the process is finished'''
        log("PyFarm :: %s :: Process (%s,%s,%s,%s,%s) complete - Exit Code: %i" % \
        (self.modName, self.processID, self.job, self.subjob, self.frame, self.id, self.process.exitCode()), 'debug')
        client = StatusClient(self.master)
        if self.process.exitCode() == 0:
            client.renderComplete(self.host, self.job, self.subjob, self.frame, self.id)
        else:
            client.renderFailed(self.host, self.job, self.subjob, self.frame, self.id, str(self.process.exitCode()))

    def processStarted(self):
        '''Run when the process has started'''
        if not self.processStartedCalled:
            log("PyFarm :: %s :: Process started(%s,%s,%s,%s,%s)" % (self.modName, self.processID, self.job, self.subjob, self.frame, self.id), 'debug')
            client = StatusClient(self.master)
            client.sendPID(self.job, self.subjob, self.frame, self.id, self.processID)
            self.processStartedCalled = 1
        else:
            pass

    def processError(self, error):
        print error

    def processStdOut(self):
        '''Emit the standard output line'''
        self.stdout.writeLine("%s%s" % (str(self.lineOut), str(QString(self.process.readAllStandardError()).trimmed())))

    def processStdErr(self):
        '''Emit the standard error line'''
        self.stdout.writeLine("%s%s" % (str(self.lineOut), str(QString(self.process.readAllStandardError()).trimmed())))

    def processFailed(self, socket, exitCode, job, frame, software, command):
        '''If the process failed, inform the master'''
        log("PyFarm :: %s :: Process has failed! Informing master [NEEDS IMPLIMENTATION?]" % self.modName, 'error')
        self.socket.close()

    def sendError(self, socket, msg):
        '''Send an error back to the client for network related issues'''
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << QString("ERROR")
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UNIT16)
        self.socket.write(reply)
        self.socket.close()

    def requestWork(self, socket, job, frame, software, command):
        '''If the command executed succefully, request more work'''
        log("PyFarm :: %s :: Requesting work...." % self.modName, 'debug')
        reply = QByteArray()
        action = QString("REQUESTING_WORK")
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << job << frame << software << command
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - UNIT16)
        self.socket.write(reply)

    def shutdownProcess(self):
        '''Shutdown the current thread and all processes'''
        self.process.kill()


class QueSlaveServer(QTcpServer):
    '''Main server thread, used to receieve and start new server threads'''
    def __init__(self, master, parent=None):
        super(QueSlaveServer, self).__init__(parent)
        self.master = master
        self.modName = 'Que.QueSlaveServer'
        self.parent = parent

    def incomingConnection(self, socketId):
        '''If incomingConnection(), start thread to handle connection'''
        log("PyFarm :: %s :: Incoming connection" % self.modName, 'debug')
        self.queSlaveThread = QueSlaveServerThread(socketId, self.master, self)
        self.connect(self.queSlaveThread, SIGNAL("finished()"), self.queSlaveThread, SLOT("deleteLater()"))
        self.queSlaveThread.start()

    def shutdown(self):
        '''Try to shutdown the QueSlave server and all threads'''
        try:
            self.queSlaveThread.shutdownProcess()
        except AttributeError:
            log("PyFarm :: %s :: No threads to terminate" % self.modName, 'warning')
        finally:
            log("PyFarm :: %s :: Shutdown complete" % self.modName, 'debug')
            self.close()


class QueClient(QTcpSocket):
    '''
    Que client used to connect to main server

    VARS:
        master -- the node containing the que to connect
        to
        port -- the port to use for the socket connection
    '''
    def __init__(self, client, port=settings.netPort('que'), parent=None):
        super(QueClient, self).__init__(parent)
        self.client = client
        self.port = port

        self.socket = QTcpSocket()
        self.request = None
        self.nextBlockSize = 0

        # setup the socket connections
        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        #self.connect(self.socket, SIGNAL("stateChanged(QAbstractSocket::SocketState)"), self.reportState)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def reportState(self, state):
        print state

    def issueRequest(self, jb, sbjb, fNum, fID, sftw, args):
        job = QString(jb)
        subjob = QString(sbjb)

        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # build the packet
        for var in (self.client, jb, sbjb, fNum, fID, sftw, args):
            packet = QString(var)
            stream << packet

        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - UNIT16)

        if self.socket.isOpen():
            self.socket.close()

        log("Connecting to %s..." % self.client, 'debug')

        # once the socket emits connected() self.sendRequest is called
        self.socket.connectToHost(self.client, self.port)
        self.socket.waitForDisconnected(800)

    def sendRequest(self):
        log("Sending work to %s..." % self.client, 'debug')
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < UNIT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break

            # prepare for info
            action = QString()
            host = QString()
            job = QString()
            subjob = QString()
            frame = QString()
            id = QString()
            pid = QString()

            # unpack the incoming packet
            stream >> action
            if action == QString("finished"):
                stream >> job >> subjob >> frame >> id >> host
                self.emit(SIGNAL("pid"), (job, subjob, frame, id, host))

            self.nextBlockSize = 0

    def serverHasStopped(self):
        log("Connection closed by waiting client @ %s" % self.client, 'warning')
        self.socket.close()

    def serverHasError(self, error):
        log(str(QString("Error: %1").arg(self.socket.errorString())), 'error')
        self.socket.close()
