'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Jan 20 2008
PURPOSE: Module used to manage ques inside of the program.
Module also includes required network functions
'''

import time
import Queue
import heapq
import socket

from PyQt4.QtCore import *
from PyQt4.QtNetwork import *

TCP_PORT = 9631

class PriorityQue(Queue.Queue):
    '''
    Priority based Queue based on Python's Queue.Queue
    '''
    def _init(self, maxsize):
        self.maxsize = maxsize
        self.queue = []

    def _qsize(self):
        #Return the number of items that are currently enqueued
        return len(self.queue)

    def _empty(self):
        #Check and see if queue is empty
        return not self.queue

    def _full(self):
        #Check and see if queue is full
        return self.maxsize > 0 and len(self.queue) >= self.maxsize

    def _put(self, item):
        #Put a new item into the que
        heapq.heappush(self.queue, item)

    def _get(self):
        #Get an item from the queue
        return heapq.heappop(self.queue)

    def put(self, item, priority=0, block=True, timeout=None):
        '''Shadow and wrap Queue's put statement to allow for a priority'''
        decorated_item = priority, time.time(), item
        Queue.Queue.put(self, decorated_item, block, timeout)

    def get(self, block=True, timeout=None):
        '''Shadow and wrap Queue own get to strip auxiliary aspsects'''
        priority, time_posted, item = Queue.Queue.get(self, block, timeout)
        return item


class TCPQueClient(QTcpSocket):
    '''TCP Socket client to send standard output to server'''
    def __init__(self, host='0.0.0.0', port=TCP_PORT, parent=None):
        self.lock = QReadWriteLock()
        super(TCPStdOutClient, self).__init__(parent)
        self.host = host
        self.port = port
        self.socket = QTcpSocket()
        self.nextBlockSize = 0
        self.request = None
        self.line = 1

        # setup the connection
        self.connect(self.socket, SIGNAL("connected()"), self.sendPut)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def put(self, command):
        '''Put a command into the que, stage 1'''
        action = QString("PUT")
        command = QString()
        self.request = QByteArray()

        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << command
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)

        if not self.socket.state() == 3:
            print "Connecting to %s..." % self.host
            self.socket.connectToHost(self.host, self.port)
            #self.sendRequest()
        else:
            self.sendRequest()

    def sendPut(self):
        '''Stage 2, send the put to the server'''


    def get(self):
        '''Get a command from the Que'''
        action = QString()
        pass

    def pack(self, job, frame, stdout, host=socket.gethostname()):
        '''Pack the information into a packet'''

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

class TCPQueThread(QThread):
    '''
    Worker TCP Que Thread, handles the interaction
    of infomation with the Que
    '''
    lock = QReadWriteLock()
    def __init__(self, socketid, que, parent):
        super(TCPServerQueThread, self).__init__(parent)
        self.socketid = socketid
        self.que = que

    def run(self):
        '''Start the server thread'''
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
                    break

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                       break

            action = QString()
            command = QString()
            stream >> action
            if action in ("GET", "PUT"):
                if action == "GET":
                    try:
                        TCPQueThread.lock.lockForRead()
                        self.emit(SIGNAL("GOT_COMMAND"), QUE.get())
                    finally:
                        TCPQueThread.lock.unlock()

                elif action == "PUT":
                    stream >> command
                    try:
                        TCPQueThread.lock.lockForWrite()
                        QUE.put(command)
                        self.emit(SIGNAL("PUT_COMMAND"))
                    finally:
                        TCPQueThread.lock.unlock()


class TCPQue(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, que, parent=None):
        super(TCPQue, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        print "Incoming Connection"
        thread = TCPQueThread(socketid, self)
        self.connect(thread, SIGNAL("GOT_COMMAND"), self.gotCommand)
        self.connect(thread, SIGNAL("PUT_COMMAND"), self.putCommand)
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

    def gotCommand(self, command):
        self.emit("GOT_COMMAND", command)

    def putCommand(self, command):
        self.emit("PUT_COMMAND")

# Setup the que object
QUE = PriorityQue()
