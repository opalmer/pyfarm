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
SIZEOF_UINT16 = 2

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


class TCPServerStdErrThread(QThread):
    '''
    Threaded TCP used to handle all incoming
    standard error information
    '''
    def __init(self, socketid, parent):
        super(TCPServerStdErrThread, self).__init__(parent)
        self.socketid = socketid
        
    def run(self):
        '''Start the server'''
        print "TCPServerStdErrThread() - DEBUG - Started Thread"
        socket = QTcpSocket()
        
        
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
        print "TCPServerStdOutThread() - DEBUG - Started Thread"
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
            
            # setup and unpack the packet
            machineAddress = QString()
            stdOutput = QString()
            stream >> stdOutput >> machineAddress
            try:
                TCPServerThread.lock.lockForRead()
            finally:
                TCPServerThread.lock.unlock()
        
class TCPServerThread(QThread):
    '''Called by TCP Server upon new connection'''
    lock = QReadWriteLock()

    def __init__(self, socketid, parent):
        super(TCPServerThread, self).__init__(parent)
        self.socketid = socketid

    def run(self):
        '''Run this one the thread is created'''
        print "TCPServerThread() - DEBUG - Started thread"

        socket =  QTcpSocket()
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
            action = QString()
            room = QString()
            date = QDate()
            stream >> action
            if action in ("BOOK", "UNBOOK"):
                stream >> room >> date
                try:
                    TCPServerThread.lock.lockForRead()
                finally:
                    TCPServerThread.lock.unlock()

                uroom = unicode(room)

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




class TCPStdOutClient(QTcpSocket):
    '''TCP Socket client to send standard output to server'''
    def __init__(self, host='localhost', port=TCP_PORT, parent=None):
        super(TCPStdOutClient, self).__init__(parent)
        self.host = host
        self.port = port
        self.socket = QTcpSocket()
        self.nextBlockSize = 0
        self.output = None
        
        # setup the connection
        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
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
        #self.localhost = socket.gethostname()
        self.output = QByteArray()
        stream = QDataStream(self.output, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)

        # pack the data
        stream << job << frame << stdout << host
        stream.device().seek(0)
        stream.writeUInt16(self.output.size() - SIZEOF_UINT16)

        # if the socket is open, close it
        if self.socket.isOpen():
            self.socket.close()

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
        self.socket.close()

    def serverHasError(self, error):
        '''Gather errors then close the connection'''
        print QString("Error: %1").arg(self.socket.errorString())
        self.socket.close()
        














class TCPClient(QTcpSocket):
    '''TCP Client to communicate with TCP server'''
    def __init__(self, host="localhost", port=TCP_PORT, parent=None):
        super(TCPClient, self).__init__(parent)
        self.host = host
        self.port = port
        self.socket = QTcpSocket()
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
            self.socket.close()

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
        self.socket.close()

    def serverHasError(self, error):
        '''Gather errors then close the connection'''
        print QString("Error: %1").arg(self.socket.errorString())
        self.socket.close()

class WakeOnLan(object):
    '''Designed to utilize wake on lan to startup a remote machine'''
    def wake( macaddress ):
        '''
        Wake computer with given mac address

        NOTE: address can either include or omit the colons.
        '''
        # if the len of macaddress = 12 do nothing
        if len(macaddress) == 12:
            pass

        # if it does not, add : every third character
        elif len(macaddress) == 12 + 5:
            sep = macaddress[2]
            macaddress = macaddress.replace(sep, '')

        # unless they did something wrong.....
        else:
            raise ValueError('Incorrect MAC address format')

        # Pad the synchronization stream.
        data = ''.join(['FFFFFFFFFFFF', macaddress * 20])
        send_data = ''

        # Split up the hex values and pack.
        for i in range(0, len(data), 2):
            send_data = ''.join([send_data,
                                 struct.pack('B', int(data[i: i + 2], 16))])

        # Broadcast it to the LAN.
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        sock.sendto(send_data, ('<broadcast>', 7))

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
