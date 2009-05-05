'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 11 2009
PURPOSE: Network modules related to the communication of standard out
and standard error logs between nodes.

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
# From PyQt
from PyQt4.QtCore import QThread, QString, QObject
from PyQt4.QtCore import SIGNAL, QDataStream, QByteArray, QIODevice, SLOT
from PyQt4.QtNetwork import QAbstractSocket, QTcpSocket, QTcpServer

# From PyFarm
from lib.ReadSettings import ParseXmlSettings
settings = ParseXmlSettings('settings.xml', skipSoftware=False)

class TCPServerStdOutThread(QThread):
    '''
    Threaded TCP Server used to handle all incoming
    standard output information.
    '''
    def __init__(self, socketid, parent):
        super(TCPServerStdOutThread, self).__init__(parent)
        self.socketid = socketid

    def run(self):
        '''Start the server'''
        socket = QTcpSocket()
        print "PyFarm :: Network.TCPServerStdOutThread :: Starting Thread"

        if not socket.setSocketDescriptor(self.socketid):
            print "setSocketDescriptor(%s) is NOT 1" % self.socketid
            self.emit(SIGNAL("error(int)"), socket.error())
            return

        # while we are connected, do this
        while socket.state() == QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QDataStream(socket) # the stream is a QDataStream
            stream.setVersion(QDataStream.Qt_4_2) # set the version of the stream
            while True:
                socket.waitForReadyRead(-1)
                if socket.bytesAvailable() >= settings.netGeneral('unit16'):
                    nextBlockSize = stream.readUInt16()
                    job = QString()
                    frame = QString()
                    stdout = QString()
                    host = QString()
                    output = QStringList()
                    stream >> job >> frame >>  host >> stdout
                    for arg in (job, frame, host, stdout):
                        output.append(arg)

                    self.emit(SIGNAL("emitStdOutLine"), output)

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                       pass

        socket.close()


class TCPServerStdOut(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(TCPServerStdOut, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        print "PyFarm :: Network.TCPServerStdOut :: Incoming Connection"
        thread = TCPServerStdOutThread(socketid, self)
        self.connect(thread, SIGNAL("emitStdOutLine"), self.emitLine)
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

    def emitLine(self, line):
        self.emit(SIGNAL("emitStdOutLine"), line)


class TCPStdOutClient(QTcpSocket):
    '''TCP Socket client to send standard output to server'''
    def __init__(self, client, port=settings.netPort('stdout'), parent=None):
        super(TCPStdOutClient, self).__init__(parent)
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

    def issueRequest(self, jb, sbjb, fNum, fID):
        job = QString(jb)
        subjob = QString(sbjb)

        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # build the packet
        for var in (self.client, jb, sbjb, fNum, fID):
            packet = QString(var)
            stream << packet

        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - UNIT16)

        if self.socket.isOpen():
            self.socket.close()

        print "Connecting to %s..." % self.client

        # once the socket emits connected() self.sendRequest is called
        self.socket.connectToHost(self.client, self.port)
        self.socket.waitForDisconnected(800)

    def sendRequest(self):
        print "Sending work to %s..." % self.client
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
                print "render complete"
                self.emit(SIGNAL("pid"), (job, subjob, frame, id, host))

            self.nextBlockSize = 0

    def serverHasStopped(self):
        print "Connection closed by waiting client @ %s" % self.client
        self.socket.close()

    def serverHasError(self, error):
        print str(QString("Error: %1").arg(self.socket.errorString()))
        self.socket.close()
