'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Jan 20 2008
PURPOSE: Module used to manage ques inside of the program.
Module also includes required network functions

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

import os
import time
import Queue
import heapq
import socket

import ReadSettings as Settings

from PyQt4.QtCore import *
from PyQt4.QtNetwork import *

# setup the required ports (adjust these settings via settings.cfg)
CFG = os.getcwd()+'/settings.cfg'
QUE_PORT = Settings.Network(CFG).QuePort()
SIZEOF_UINT16 = 2

stateHelp = {0:"The socket is not connected",
                        1:"The socket is performing a host name lookup",
                        2:"The socket has started establishing a connection",
                        3:"A connection is established",
                        4:"The socket is bound to an address and port (for servers)",
                        5:"The socket is about to close (data may still be waiting to be written)"}

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


class QueServerThread(QThread):
    '''Main que server thread, meant to be run on the master computer'''
    lock = QReadWriteLock()

    def __init__(self, socketId, parent):
        super(Thread, self).__init__(parent)
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
                    break

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                        break

            action = QString()
            command = QString()

            stream >> action
            if action in ("GET_FRAME", "GET_QUE_SIZE"):
                if action == "GET_FRAME":
                    try:
                        QueServerThread.lock.lockForWrite()
                        job = QUE.get()
                        command = [job[0], job[1], job[2]]
                    finally:
                        Thread.lock.unlock()
                        self.sendReply(socket, job)
                else:
                    try:
                        QueServerThread.lock.lockForRead()
                        error = QString("Error: %s is not a valid action").arg(action)
                    finally:
                        QueServerThread.lock.unlock()
                    self.sendError(socket, error)
                    error = None
                self.waitForDisconnected()

        def sendError(self, socket, msg):
            '''Send an error back to the client'''
            reply = QByteArray()
            stream = QDataStream(reply, QIODevice.WriteOnly)
            stream.setVersion(QDataStream.Qt_4_2)
            stream.writeUInt16(0)
            # stream << ACTION << COMMAND
            stream << QString("ERROR") << QString(msg)
            stream.device().seek(0)
            stream.writeUInt16(reply.size() - SIZEOF_UINT16)
            socket.write(reply)

        def sendReply(self, socket, cmd):
            '''Send a new job frame and command back to the client'''
            reply = QByteArray()
            stream = QDataStream(reply, QIODevice.WriteOnly)
            stream.setVersion(QDataStream.Qt_4_2)
            stream.writeUInt16(0)
            stream << cmd
            stream.device().seek(0)
            stream.writeUInt16(reply.size() - SIZEOF_UINT16)
            socket.write(reply)

class QueServer(QTcpServer):
    '''Main server thread, used to receieve and start new server threads'''
    def __init__(self, parent=None):
        super(QueServer, self).__init__(parent)

    def incomingConnection(self, socketId):
        '''If incomingConnection(), start thread to handle connection'''
        thread = QueServerThread(socketId, self)
        self.connect(thread, SIGNAL("finished()"),thread, SLOT("deleteLater()"))
        self.connect(thread, SIGNAL("stateChanged(QAbstractSocket::SocketState)"), self.reportState)
        thread.start()

    def reportState(self, state):
        print stateHelp[state]


class QueClient(QTcpSocket):
    '''
    Que client used to connect to main server

    VARS:
        master -- the node containing the que to connect
        to
        port -- the port to use for the socket connection
    '''
    def __init__(self, master, parent=None, port=QUE_PORT):
        super(QueClient, self).__init__(parent)
        self.master = master
        self.port = port

        # setup the socket
        self.socket = QTcpSocket()
        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("stateChanged(QAbstractSocket::SocketState)"), self.reportState)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)


    def reportState(self, state):
        print stateHelp[state]

    def issueRequest(self, action):
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)
        if self.socket.isOpen():
            self.socket.close()
        print "Connecting to server..."

        # once the socket emits connected() self.sendRequest is called
        self.socket.connectToHost(self.master, self.port)

    def sendRequest(self):
        print "Sending request..."
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < SIZEOF_UINT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break

            # prepare for info
            action = QString()
            command = QString()

            # unpack the incoming packet (action first)
            stream >> action
            if action != "ERROR":
                stream >> command
                print command
            if action == "ERROR":
                msg = QString("Error: %1").arg(command)
            print mst
            self.nextBlockSize = 0

    def serverHasStopped(self):
        print "Error: Connection close by Que Server"
        self.socket.close()

    def serverHasError(self, error):
        print str(QString("Error: %1").arg(self.socket.errorString()))
        self.socket.close()

class WaitOnQueThread(QThread):
    '''Wait for the master to ready the que, then receive the first command'''
    lock = QReadWriteLock()

    def __init__(self, socketId, parent):
        super(WaitOnQueThread, self).__init__(parent)
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
                    break

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                        break

            action = QString()

            stream >> action
            if action == "QUE_READY":
                try:
                    WaitOnQueThread.lock.lockForWrite()
                    reply = QString("ENTERING_QUE")
                finally:
                    Thread.lock.unlock()
                    self.sendReply(socket, reply)
            else:
                try:
                    WaitOnQueThread.lock.lockForRead()
                    error = QString("Error: %s is not a valid action").arg(action)
                finally:
                    WaitOnQueThread.lock.unlock()
                error = None
            self.waitForDisconnected()

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

        def sendReply(self, socket, cmd):
            '''Send a new job frame and command back to the client'''
            reply = QByteArray()
            stream = QDataStream(reply, QIODevice.WriteOnly)
            stream.setVersion(QDataStream.Qt_4_2)
            stream.writeUInt16(0)
            stream << cmd
            stream.device().seek(0)
            stream.writeUInt16(reply.size() - SIZEOF_UINT16)
            socket.write(reply)


class WaitOnQue(QTcpServer):
    '''Main server thread, used to receieve and start new server threads'''
    def __init__(self, parent=None):
        super(WaitOnQue, self).__init__(parent)

    def incomingConnection(self, socketId):
        '''If incomingConnection(), start thread to handle connection'''
        print "Got incoming que connection..."
        thread = WaitOnQueThread(socketId, self)
        self.connect(thread, SIGNAL("finished()"),thread, SLOT("deleteLater()"))
        thread.start()


class SendQueReady(QTcpSocket):
    '''
    Que client used to connect to main server

    VARS:
        master -- the node containing the que to connect
        to
        port -- the port to use for the socket connection
    '''
    def __init__(self, client, port=QUE_PORT, parent=None):
        super(SendQueReady, self).__init__(parent)
        self.client = client
        self.port = port

        self.socket = QTcpSocket()
        self.request = None
        self.nextBlockSize = 0

        # setup the socket connections
        self.connect(self.socket, SIGNAL("connected()"), self.sendReady)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("stateChanged(QAbstractSocket::SocketState)"), self.reportState)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def sendReady(self):
        self.issueRequest(QString("QUE_READY"))

    def reportState(self, state):
        print stateHelp[state]

    def issueRequest(self, action):
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)

        if self.socket.isOpen():
            self.socket.close()

        print "Connecting to %s..." % self.client

        # once the socket emits connected() self.sendRequest is called
        self.socket.connectToHost('10.56.1.51', 9633)

    def sendRequest(self):
        print "Sending que ready to %s..." % self.client
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < SIZEOF_UINT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break

            # prepare for info
            action = QString()

            # unpack the incoming packet (action first)
            stream >> action
            if action != "ERROR":
                print "Reply from %s error retrieving first command..." % self.client
            if action == "ENTERING_QUE":
                print "%s is entering the que..." % self.client
            self.nextBlockSize = 0

    def serverHasStopped(self):
        print "Error: Connection close by waiting client"
        self.socket.close()

    def serverHasError(self, error):
        print str(QString("Error: %1").arg(self.socket.errorString()))
        self.socket.close()

# Setup the que object
QUE = PriorityQue()
