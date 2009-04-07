'''
AUTHOR: Oliver Palmer
HOMEPAGE: www.pyfarm.net
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

# Qt libs (Explicit imports for speed)
#  first the main objects
from PyQt4.QtCore import QThread, QObject, SIGNAL, SLOT
#  data storage
from PyQt4.QtCore import QDataStream, QString, QByteArray, QReadWriteLock, QIODevice
# network related
from PyQt4.QtNetwork import QTcpServer, QTcpSocket, QAbstractSocket, QHostInfo

# PyFarm Libs
from ReadSettings import ParseXmlSettings
from Info import System

settings = ParseXmlSettings('%s/settings.xml' % os.getcwd(), skipSoftware=False)

class BroadcastServer(QThread):
    '''
    Threaded server to recieve a multicast packet and get the client ip/port

    INPUT:
        parent (str) - the thread to parent to. Example: a = MulticastServer(self)
        port (int) - incoming number, defaults to settings.netPort('broadcast')
        host (str) - host to bind to UDP, defaults to ALL
        timeout (int) - timeout the operation after this amount of time

      NOTE: Get hostname with socket.hostname() <- might be required by QTcpServer
    '''
    def __init__(self,  parent, host='', timeout=5):
        super(BroadcastServer,  self).__init__(parent)
        print "PyFarm :: Network.BroadcastServer :: Sending Broadcast"
        self.port = settings.netPort('broadcast')
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
            print "PyFarm :: Network.BroadcastServer :: Broadcast Complete"
            self.emit(SIGNAL('DONE'), self.done)


class BroadcastClient(QThread):
    '''
    Threaded client to recieve a multicast packet and inform the server of ip and port

    INPUT:
        port (int) - incoming number, defaults to settings.netPort('broadcast')
        host (str) - host to bind to UDP, defaults to ALL
        parent - None.  By parenting to none it the client runs on its own, not bound to original thread.

    OUTPUT:
        address (None) - Example: ['10.56.1.5', 51423]

    NOTE: Get hostname with socket.hostname() <- might be required by QTcpServer
    '''
    def __init__(self, host='', parent=None):
        super(BroadcastClient,  self).__init__(parent)
        self.port = settings.netPort('broadcast')
        self.host = host
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

    def run(self):
        '''Receieve the broadcast packet and reply to the host'''
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        self.sock.bind((self.host, self.port)) #setup a socket to receieve a connection
        print "PyFarm :: Network.BroadcastClient :: Waiting for Broadcast..."

        while True:
            try:
                # this loop is run ever time a connection comes in
                message, address = self.sock.recvfrom(8192) # get the connection info and message

                # Now, reply back to the server with our address and message
                self.sock.sendto("I'm a client, my name is %s" % socket.gethostname(), address)
                print "PyFarm :: Network.BroadcastClient :: Found master @ %s" % address[0]

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
    def __init__(self, host='0.0.0.0', port=settings.netPort('stdout'), parent=None):
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


class WaitForQueServer(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(WaitForQueServer, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        print "PyFarm :: Network.WaitForQueServer :: Incoming Connection!"
        thread = TCPServerStdOutThread(socketid, self)
        self.connect(thread, SIGNAL("emitStdOutLine"), self.emitLine)
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

    def emitLine(self, line):
        self.emit(SIGNAL("emitStdOutLine"), line)


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
        stream.writeUInt16(reply.size() - settings.netGeneral('unit16'))
        socket.write(reply)

    def sendReply(self, socket, action, options):
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << options
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - settings.netGeneral('unit16'))
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
        stream.writeUInt16(reply.size() - settings.netGeneral('unit16'))

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
        print "PyFarm :: %s :: Incoming connection" % self.modName
        self.serverThread = AdminServerThread(socketId, self)
        self.connect(self.serverThread, SIGNAL("finished()"), self.serverThread, SLOT("deleteLater()"))
        self.connect(self.serverThread, SIGNAL("SHUTDOWN"), self.emitShutdown)
        self.connect(self.serverThread, SIGNAL("RESTART"), self.emitRestart)
        self.serverThread.start()

    def shutdown(self):
        '''Shutdown the server and try to terminate all threads'''
        self.serverThread.quit()
        self.serverThread.wait()
        self.close()

    def emitShutdown(self):
        '''
        After receiving the shutdown signal from the thread,
        emit SHUTDOWN to the parent.
        '''
        print "PyFarm :: %s :: Broadcasting shutdown signal" % self.modName
        self.emit(SIGNAL("SHUTDOWN"))

    def emitRestart(self):
        '''
        After receiving the restart signal from the thread,
        emit RESTART to the parent.
        '''
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
        stream.writeUInt16(self.request.size() - settings.netGeneral('unit16'))

        if self.socket.isOpen():
            print "PyFarm :: %s :: Already connected, closing socket" % self.modName
            self.socket.close()

        print "PyFarm :: %s :: Connecting to %s" % (self.modName, self.client)
        self.socket.connectToHost(self.client, self.port)
        self.socket.waitForDisconnected(800) # wait to finish transmission to each admin server

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

            if action in ("SHUTDOWN", "RESTART", "SYSINFO"):
                if action == "SHUTDOWN":
                    print "PyFarm :: %s :: %s is shutting down" % (self.modName, self.client)
                elif action == "RESTART":
                    print "PyFarm :: %s :: %s is restarting" % (self.modName, self.client)
                elif action == "SYSINFO":
                    stream >> options
                    self.socket.close()
                    self.emit(SIGNAL("newSysInfo"), options)

    def serverHasStopped(self):
        print "PyFarm :: %s :: %s has stopped" % (self.modName, self.client)
        self.socket.close()

    def serverHasError(self, error):
        print "PyFarm :: %s :: %s has error: %s" % (self.modName, self.client, self.socket.errorString())
        self.socket.close()


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
