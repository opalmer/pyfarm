'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Dec 16 2008
PURPOSE: Network modules used to help facilitate network communication

    This file is part of PyFarm.

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
# python libs
import os
import sys
import time
import socket
import os.path
# Qt libs
from PyQt4.QtCore import *
from PyQt4.QtNetwork import *
# PyFarm Libs
import ReadSettings as Settings

# settings and ports for network usage (adjust these settings via ./settings.cfg)
SIZEOF_UINT16 = Settings.Network().Unit16Size()
BROADCAST_PORT = Settings.Network().BroadcastPort()
QUE_PORT = Settings.Network().QuePort()
STDOUT_PORT = Settings.Network().StdOutPort()
STDERR_PORT = Settings.Network().StdErrPort()

class BroadcastServer(QThread):
    '''
    Threaded server to recieve a multicast packet and get the client ip/port

    INPUT:
        parent (str) - the thread to parent to. Example: a = MulticastServer(self)
        port (int) - incoming number, defaults to BROADCAST_PORT
        host (str) - host to bind to UDP, defaults to ALL
        timeout (int) - timeout the operation after this amount of time

      NOTE: Get hostname with socket.hostname() <- might be required by QTcpServer
    '''
    def __init__(self,  parent, host='', timeout=5):
        super(BroadcastServer,  self).__init__(parent)
        self.port = BROADCAST_PORT
        self.host = host
        self.dest = ('<broadcast>', self.port)
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self.sock.settimeout(timeout)
        self.done = False

    def run(self):
        '''Send the broadcast packet accross the network'''
        try:
            nodes = []
            # get ready to broadcast
            self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
            self.sock.sendto("I'm a server, my name is %s" % socket.gethostname(), self.dest)
            #print "Looking for nodes; press Ctrl-C to stop."

            while True:
                (message, address) = self.sock.recvfrom(2048)
                self.emit(SIGNAL('gotNode'), '%s' % address[0])

        except (socket.timeout,  socket.error):
            pass

        finally:
            self.done = True
            self.emit(SIGNAL('DONE'), self.done)


class BroadcastClient(QThread):
    '''
    Threaded client to recieve a multicast packet and inform the server of ip and port

    INPUT:
        port (int) - incoming number, defaults to BROADCAST_PORT
        host (str) - host to bind to UDP, defaults to ALL
        parent - None.  By parenting to none it the client runs on its own, not bound to original thread.

    OUTPUT:
        address (None) - Example: ['10.56.1.5', 51423]

    NOTE: Get hostname with socket.hostname() <- might be required by QTcpServer
    '''
    def __init__(self, host='', parent=None):
        super(BroadcastClient,  self).__init__(parent)
        self.port = BROADCAST_PORT
        self.host = host
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

    def run(self):
        '''Receieve the broadcast packet and reply to the host'''
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        self.sock.bind((self.host, self.port)) #setup a socket to receieve a connection
        print "Waiting on master computer..."

        while True:
            try:
                # this loop is run ever time a connection comes in
                message, address = self.sock.recvfrom(8192) # get the connection info and message

                # Now, reply back to the server with our address and message
                self.sock.sendto("I'm a client, my name is %s" % socket.gethostname(), address)
                print "Found master @ %s" % address[0]

            finally:
                return address[0]


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
        #print "TCPServerStdOutThread() - DEBUG - Started Thread"
        socket = QTcpSocket()
        print "Running thread"


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
                if socket.bytesAvailable() >= SIZEOF_UINT16:
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
        print "Incoming Connection"
        thread = TCPServerStdOutThread(socketid, self)
        self.connect(thread, SIGNAL("emitStdOutLine"), self.emitLine)
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

    def emitLine(self, line):
        self.emit(SIGNAL("emitStdOutLine"), line)


class TCPStdOutClient(QTcpSocket):
    '''TCP Socket client to send standard output to server'''
    def __init__(self, host='0.0.0.0', port=STDOUT_PORT, parent=None):
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
        stream.writeUInt16(self.output.size() - SIZEOF_UINT16)

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
        print "Server is disconnected"
        self.socket.disconnectFromHost()
        self.emit(SIGNAL("serverDied"))

    def serverHasError(self, error):
        '''Gather errors then close the connection'''
        print QString("Error: %1").arg(self.socket.errorString())
        self.socket.disconnectFromHost()


class WaitForQueServerThread(QThread):
    '''
    Threaded TCP Server used to handle all incoming
    standard output information.
    '''
    lock = QReadWriteLock()
    def __init__(self, socketid, parent):
        super(WaitForQueServerThread, self).__init__(parent)
        self.socketid = socketid

    def run(self):
        '''Start the server'''
        socket = QTcpSocket()

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
                if socket.bytesAvailable() >= SIZEOF_UINT16:
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


class WaitForQueServer(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(WaitForQueServer, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        print "Incoming Connection"
        thread = TCPServerStdOutThread(socketid, self)
        self.connect(thread, SIGNAL("emitStdOutLine"), self.emitLine)
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

    def emitLine(self, line):
        self.emit(SIGNAL("emitStdOutLine"), line)


class AdminServerThread(QThread):
    '''Administrator server thread used to manage the client'''
    lock = QReadWriteLock()

    def __init__(self, socketId, parent):
        super(QueSlaveServerThread, self).__init__(parent)
        self.socketId = socketId

    def run(self):
        '''Run the server thread and process the requested data'''
        socket = QTcpSocket()

        if not socket.setSocketDescriptor(self.socketId):
            self.emit(SIGNAL("error(int)"), socket.error())
            return

        while socket.state() == QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QDataStream(socket)
            stream.setVersion(QDataStream.Qt_4_2)

            while True:
                socket.waitForReadyRead(-1)
                if socket.bytesAvailable() >= SIZEOF_UINT16:
                    nextBlockSize = stream.readUInt16()

                    JOB = QString()
                    FRAME = QString()
                    SOFTWARE = QString()
                    COMMAND = QString()

                    print "Unpacking the command..."
                    stream >> JOB >> FRAME >> SOFTWARE >> COMMAND
                    if JOB == 'TERMNATE_SELF':
                        socket.close()
                    else:
                        print "Running Render..."
                        SWPATH = LOCAL_SOFTWARE[str(SOFTWARE)].split("::")[0]
                        os.system("%s %s" % (SWPATH, COMMAND))

                        ACTION = QString("REQUESTING_WORK")
                        self.sendReply(socket, ACTION, JOB, FRAME, SOFTWARE, COMMAND)
                        socket.waitForDisconnected()

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                        break

    def sendError(self, socket, msg):
        '''Send an error back to the client'''
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << QString("ERROR")
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - SIZEOF_UINT16)
        socket.write(reply)
        socket.close()

    def sendReply(self, socket, ACTION, JOB, FRAME, SOFTWARE, COMMAND):
        print "Requesting more work..."
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << ACTION << JOB << FRAME << SOFTWARE << COMMAND
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - SIZEOF_UINT16)
        socket.write(reply)


class AdminServer(QTcpServer):
    '''Main admin server thread, used to receieve and start new admin server threads'''
    def __init__(self, parent=None):
        super(AdminServer, self).__init__(parent)

    def incomingConnection(self, socketId):
        '''If incomingConnection(), start thread to handle connection'''
        print "Incoming!"
        thread = QueSlaveServerThread(socketId, self)
        self.connect(thread, SIGNAL("finished()"),thread, SLOT("deleteLater()"))
        thread.start()

def ResolveHost(host):
    '''
    Given IP address or hostname, return hostname and IP

    VARS:
        host (string) - hostname or IP address

    OUTPUT:
        list2 - [hostname, address]
    '''
    output = []
    try:
        output.append(socket.gethostbyaddr(host)[0])
    except (socket.gaierror, socket.herror):
        return "BAD_HOST"

    try:
        output.append(socket.gethostbyaddr(host)[2][0])
    except (socket.gaierror, socket.herror):
        return "BAD_HOST"

    return output


def GetLocalIP(master):
    '''Get the ip address of the local computer'''
    from socket import socket, SOCK_DGRAM, AF_INET
    s = socket(AF_INET, SOCK_DGRAM)
    s.connect((master, 0))
    return s.getsockname()[0]
