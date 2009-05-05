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
    lock = QReadWriteLock()
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
    def __init__(self, host, port=settings.netPort('stdout'), parent=None):
        print "PyFarm :: Network.TCPStdOutClient :: Starting Client"
        self.lock = QReadWriteLock()
        super(TCPStdOutClient, self).__init__(parent)
        self.host = host
        self.port = port
        self.socket = QTcpSocket()
        self.nextBlockSize = 0
        self.output = None
        self.line = 1

        # setup the connection
        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def  shutdown(self):
        self.pack('hello', '1', 'this is the mnessage')

    def pack(self, job, frame, stdout, host=socket.gethostname()):
        '''
        Pack the information into a packet

        VARS:
            action (string) - action to perform
                    +render - render the give frame
                    +status - current status of the render
                        - waiting
                        - running
                        - failed
                    +kill - if rendering, STOP
                    +log - if rendering, tail the process log
            software (string) - software to render with
            options (string) - string of render options
            job (string) - job NUMBER
            frame (string) - frame to render, query, etc.
        '''

        job = QString(job)
        frame = QString(frame)
        host = QString(host)
        stdout = QString(stdout)
        self.output = QByteArray()
        stream = QDataStream(self.output, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # pack the data
        stream << job << frame << host << stdout
        stream.device().seek(0)
        stream.writeUInt16(self.output.size() - settings.netGeneral('unit16'))

        # once the socket emits connected() self.sendRequest is called
        if not self.socket.state() == 3:
            print "Connecting to %s..." % self.host
            self.socket.connectToHost(self.host, self.port)
            #self.sendRequest()
        else:
            self.sendRequest()

    def sendRequest(self):
        '''Send the packed packet'''
        self.nextBlockSize = 0
        print "%i - Sending Line" % self.line
        self.socket.write(self.output)
        self.line +=1
        self.output = None

    def serverHasStopped(self):
        '''If the server has stopped or been shutdown, close the socket'''
        print "PyFarm :: Network.TCPStdOutClient :: Disconnected"
        self.socket.disconnectFromHost()
        self.emit(SIGNAL("serverDied"))

    def serverHasError(self, error):
        '''Gather errors then close the connection'''
        print QString("Error: %1").arg(self.socket.errorString())
        self.socket.disconnectFromHost()
