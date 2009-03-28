'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
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
# Python Libs
import os
import time
import Queue
import heapq
import socket

# PyFarm Libs
from Process import *
from ReadSettings import ParseXmlSettings
from RenderConfig import SoftwareInstalled

# PyQt Libs
from PyQt4.QtCore import *
from PyQt4.QtNetwork import *

settings = ParseXmlSettings('%s/settings.xml' % os.getcwd())

stateHelp = {0:"The socket is not connected",
                        1:"The socket is performing a host name lookup",
                        2:"The socket has started establishing a connection",
                        3:"A connection is established",
                        4:"The socket is bound to an address and port (for servers)",
                        6:"The socket is about to close (data may still be waiting to be written)"}

# setup and find all of the local software
LOCAL_SOFTWARE = {}
software = SoftwareInstalled()

# find the installed software and add it the LOCAL_SOFTWARE
LOCAL_SOFTWARE.update(software.maya())
LOCAL_SOFTWARE.update(software.houdini())
LOCAL_SOFTWARE.update(software.shake())

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

    def size(self):
        '''Return the size of the que'''
        return self.qsize()

    def emptyQue(self):
        '''Empty the que'''
        for i in range(1, QUE.qsize()+1):
            QUE.get()


class QueSlaveServerThread(QThread):
    '''Wait for the master to ready the que, then receive the first command'''
    lock = QReadWriteLock()

    def __init__(self, socketId, parent):
        super(QueSlaveServerThread, self).__init__(parent)
        self.socketId = socketId
        self.modName = 'Que.QueSlaveServerThread'

    def run(self):
        '''Run the server thread and process the requested data'''
        socket = QTcpSocket()
        print "PyFarm :: %s :: Starting thread" % self.modName

        if not socket.setSocketDescriptor(self.socketId):
            self.emit(SIGNAL("error(int)"), socket.error())
            return

        while socket.state() == QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QDataStream(socket)
            stream.setVersion(QDataStream.Qt_4_2)

            while True:
                socket.waitForReadyRead(-1)
                if socket.bytesAvailable() >= settings.netGeneral('unit16'):
                    nextBlockSize = stream.readUInt16()

                    JOB = QString()
                    FRAME = QString()
                    SOFTWARE = QString()
                    COMMAND = QString()

                    print "PyFarm :: %s :: Unpacking the command" % self.modName
                    stream >> JOB >> FRAME >> SOFTWARE >> COMMAND
                    if JOB == 'TERMNATE_SELF':
                        print "PyFarm :: %s :: Closing connection" % self.modName
                        socket.close()
                    else:
                        program = LOCAL_SOFTWARE[str(SOFTWARE)].split("::")[0]
                        scene = COMMAND.split(' ')[len(COMMAND.split(' '))-1]

                        print "PyFarm :: %s :: Running render" % self.modName
                        # now, run the command
                        #os.system("%s %s" % (program, COMMAND))
                        print "PyFarm :: %s :: Sending command(%s,%s)" % (self.modName, program, COMMAND)
                        process = RunProcess(program, COMMAND)


                        ACTION = QString("REQUESTING_WORK")
                        self.sendReply(socket, ACTION, JOB, FRAME, SOFTWARE, COMMAND)
                        socket.waitForDisconnected()

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                        break

    def readStdOut(self, line):
        print QString(self.process.readAllStandardError()).trimmed()

    def sendError(self, socket, msg):
        '''Send an error back to the client'''
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << QString("ERROR")
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - settings.netGeneral('unit16'))
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
        stream.writeUInt16(reply.size() - settings.netGeneral('unit16'))
        socket.write(reply)


class QueSlaveServer(QTcpServer):
    '''Main server thread, used to receieve and start new server threads'''
    def __init__(self, parent=None):
        super(QueSlaveServer, self).__init__(parent)
        self.modName = 'Que.QueSlaveServer'

    def incomingConnection(self, socketId):
        '''If incomingConnection(), start thread to handle connection'''
        print "PyFarm :: %s :: Incoming connection" % self.modName
        self.queSlaveThread = QueSlaveServerThread(socketId, self)
        self.connect(self.queSlaveThread, SIGNAL("finished()"), self.queSlaveThread, SLOT("deleteLater()"))
        self.queSlaveThread.start()

    def shutdown(self):
        '''Try to shutdown the QueSlave server and all threads'''
        try:
            self.queSlaveThread.quit()
            self.queSlaveThread.wait(800)
        except AttributeError:
            print "PyFarm :: %s :: No threads to terminate" % self.modName
        finally:
            print "PyFarm :: %s :: Shutdown complete" % self.modName
            self.close()


class SendCommand(QTcpSocket):
    '''
    Que client used to connect to main server

    VARS:
        master -- the node containing the que to connect
        to
        port -- the port to use for the socket connection
    '''
    def __init__(self, client, port=settings.netPort('que'), parent=None):
        super(SendCommand, self).__init__(parent)
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
        print stateHelp[state]

    def issueRequest(self, inList):
        if inList == 'TERMINATE_SELF':
            print "It is time to die!"
            self.socket.close()
        else:
            JOB = QString(inList[0])
            FRAME = QString(inList[1])
            SOFTWARE = QString(inList[2])
            COMMAND = QString(inList[3])

            # use these for checks
            self.job = JOB
            self.frame = FRAME
            self.command = COMMAND

            self.request = QByteArray()
            stream = QDataStream(self.request, QIODevice.WriteOnly)
            stream.setVersion(QDataStream.Qt_4_2)
            stream.writeUInt16(0)
            stream << JOB << FRAME << SOFTWARE << COMMAND
            stream.device().seek(0)
            stream.writeUInt16(self.request.size() - settings.netGeneral('unit16'))

            if self.socket.isOpen():
                self.socket.close()

            print "Connecting to %s..." % self.client

            # once the socket emits connected() self.sendRequest is called
            self.socket.connectToHost(self.client, settings.netPort('que'))

    def sendRequest(self):
        print "Sending work to %s..." % self.client
        self.emit(SIGNAL("SENDING_WORK"), [self.client, self.job, self.frame])
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < settings.netGeneral('unit16'):
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break

            # prepare for info
            ACTION = QString()
            JOB = QString()
            FRAME = QString()
            SOFTWARE = QString()
            COMMAND = QString()

            # unpack the incoming packet
            stream >> ACTION
            if ACTION == QString("REQUESTING_WORK"):
                stream >> JOB >> FRAME >> SOFTWARE >> COMMAND
                trueState = 0 # must == 3 to pass
                if JOB == self.job:
                    trueState += 1
                if FRAME == self.frame:
                    trueState += 1
                if COMMAND == self.command:
                    trueState += 1
                if trueState == 3:
                    self.emit(SIGNAL("WORK_COMPLETE"), [self.client, JOB, FRAME])

            self.nextBlockSize = 0

    def serverHasStopped(self):
        print "Connection closed by waiting client @ %s" % self.client
        self.socket.close()

    def serverHasError(self, error):
        print str(QString("Error: %1").arg(self.socket.errorString()))
        self.socket.close()

# Setup the que object
QUE = PriorityQue()
