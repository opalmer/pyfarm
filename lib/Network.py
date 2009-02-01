'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 16 2008
PURPOSE: Network modules used to help facilitate network communication
'''
# python libs
import sys
import time
import socket
# pyfarm libs
from Info import System
from FarmLog import FarmLog
# Qt libs
from PyQt4.QtCore import *
from PyQt4.QtNetwork import *
from PyQt4.QtGui import * # < - tmp

## SETUP SOME STANDARD VARS FOR NETWORK USE
SIZEOF_UINT16 = 32

## SETUP PORTS FOR NETWORK (Range: 9630-9699)
BROADCAST_PORT = 9630
TCP_PORT = 9631
UDP_PORT = 9632

class BroadcastServer(QThread):
    '''
    Threaded client to recieve a multicast packet and inform the server of ip and port

    REQUIRES:
        Python:
            socket

        PyQt:
            QThread

        PyFarm:
            FarmLog

    INPUT:
        parent (str) - the thread to parent to. Example: a = MulticastServer(self)
        port (int) - incoming number, defaults to 51423
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
            print "Looking for nodes; press Ctrl-C to stop."

            while True:
                (message, address) = self.sock.recvfrom(2048)
                print "Found Node: %s" % address[0]
                self.emit(SIGNAL('gotNode'), '%s' % address[0])

        except (socket.timeout,  socket.error):
            pass

        finally:
            self.done = True
            self.emit(SIGNAL('DONE'), self.done)


class BroadcastClient(QThread):
    '''
    Threaded client to recieve a multicast packet and inform the server of ip and port

    REQUIRES:
        Python:
            socket

        PyQt:
            QThread

        PyFarm:
            FarmLog

    INPUT:
        port (int) - incoming number, defaults to 51423
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
        print "Looking for server; press Ctrl-C to stop."

        while True:
            try:
                # this loop is run ever time a connection comes in
                message, address = self.sock.recvfrom(8192) # get the connection info and message
                print "Found Server: %s" % address[0]

                # Now, reply back to the server with our address and message
                self.sock.sendto("I'm a client, my name is %s" % socket.gethostname(), address)

            except (KeyboardInterrupt, SystemExit):
                sys.exit('PROGRAM TERMINATED')


class TCPServerStdOutThread(QThread):
    '''
    Threaded TCP Server used to handle all incoming
    standard output information.
    '''
    def __init__(self, socketid, parent):
        super(TCPServerStdOutThread, self).__init__(parent)
        self.socketid = socketid

    def run(self):
        '''Start the server thread'''
        print "Starting TCP Server Thread"
        socket = QTcpSocket()

        if not socket.setSocketDescriptor(self.socketid):
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
                    break

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                        break

        job = QString()
        frame = QString()
        host = QString()
        stdout = QString()
        output = QStringList()

        stream >> job >> frame >> host >> stdout

        for arg in (job, frame, stdout, host):
            output.append(arg)

        self.emit(SIGNAL("emitStdOutLine"), output)

class TCPServerStdOut(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(TCPServerStdOut, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        print "Got incoming connection"
        thread = TCPServerStdOutThread(socketid, self)
        #print "TCPServer() - DEBUG - Got incoming connection at %s" % socketid
        self.connect(thread, SIGNAL("emitStdOutLine"), self.emitLine)
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

    def emitLine(self, line):
        self.emit(SIGNAL("emitStdOutLine"), line)


class TCPServerStdOutThread2(QThread):
    '''
    Threaded TCP Server used to handle all incoming
    standard output information.
    '''
    lock = QReadWriteLock()
    def __init__(self, socketid, parent):
        super(TCPServerStdOutThread2, self).__init__(parent)
        self.socketid = socketid

    def run(self):
        '''Start the server'''
        #print "TCPServerStdOutThread() - DEBUG - Started Thread"
        socket = QTcpSocket()
        #socket.setReadBufferSize(500000000)
        #socket.setWriteBufferSize(500000000)
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
                #try:
                    #TCPServerStdOutThread2.lock.lockForRead()
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

class TCPServerStdOut2(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(TCPServerStdOut2, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        print "Incoming Connection"
        thread = TCPServerStdOutThread2(socketid, self)
        self.connect(thread, SIGNAL("emitStdOutLine"), self.emitLine)
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

    def emitLine(self, line):
        self.emit(SIGNAL("emitStdOutLine"), line)

class TCPStdOutClient(QTcpSocket):
    '''TCP Socket client to send standard output to server'''
    def __init__(self, host='0.0.0.0', port=TCP_PORT, parent=None):
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


class TCPServer(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(TCPServer, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        thread = TCPServerThread(socketid, self)
        print "TCPServer() - DEBUG - Got incoming connection at %s" % socketid
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()


class TCPClient(QTcpSocket):
    '''TCP Client to communicate with TCP server'''
    def __init__(self, host="localhost", port=TCP_PORT, parent=None):
        super(TCPClient, self).__init__(parent)
        self.host = host
        self.port = port
        self.socket = QTcpSocket(parent)
        self.nextBlockSize = 0
        self.request = None

        # setup the connections
        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def pack(self, action, software, options, job, frame):
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
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # pack the data
        stream << action << software << options << job << frame
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)

        # if the socket is open, close it
        if self.socket.isOpen():
            self.socket.disconnectFromHost()

        # once the socket emits connected() self.sendRequest is called
        self.socket.connectToHost(self.host, self.port)

    def sendRequest(self):
        '''Send the packed packet'''
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        '''Read the response from the server'''
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        # while there is streaming data
        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < SIZEOF_UINT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break

            action = QString()
            software = QString()
            options = QString()
            job = QString()
            frame = QString()
            scene = QString()

            stream >> action >> job >> frame
            if action == "ERROR":
                msg = QString("Error: %1").arg(command)
            elif action == "RENDER":
                msg = QString("Rendering frame %2 of job %1").arg(job).arg(frame)
                self._updateStatus('TCPServer', msg, 'green')
            self.nextBlockSize = 0

    def serverHasStopped(self):
        '''If the server has stopped or been shutdown, close the socket'''
        self.socket.disconnectFromHost()

    def serverHasError(self, error):
        '''Gather errors then close the connection'''
        print QString("Error: %1").arg(self.socket.errorString())
        self.socket.disconnectFromHost()

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
