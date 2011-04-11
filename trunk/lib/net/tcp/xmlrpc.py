'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 10 2011
PURPOSE: To provide a PyQt compatible xmlrpc server

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys
import fnmatch

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import logger, system, net

UNIT16         = 8
STREAM_VERSION = net.dataStream()
logger         = logger.Logger('test', 'test')

#class SSLParms(QtCore.QObject):
    #'''
    #Place holder class to hold information about an
    #SSL certificate
    #'''
    #def __init__(self, certFile, keyFile, parent=None):
        #super(SSLParms, self).__init__(parent)
        #self.cert = QtCore.QFile(certFile, parent)
        #self.cert.open(QtCore.QFile.ReadOnly)
        #certificate = QtNetwork.QSslCertificate(self.cert)
        #self.key  = QtCore.QString(keyFile)

class Protocol(QtCore.QObject):
    def __init__(self, socket, timeout=5000, parent=None):
        super(Protocol, self).__init__(parent)
        self.socket  = socket
        self.timeout = timeout
        self.timerId = None

    def _readyRead(self):
        print "HERE_@!!!"
        logger.netserver("Socket is ready to read")
        self.restartTimeout()

    def _bytesWritten(self, bytes):
        logger.netserver("Socket has written %i bytes" % bytes)
        self.restartTimeout()

    def _killTimer(self, timerId):
        logger.warning("Killing Timer: %i" % timerId)
        self.killTimer(timerId)

    def getSocket(self): return self.socket

    def timerEvent(self, event):
        if event.timerId() == self.timerId:
            logger.debug("Emitted Timer Event: %i" % self.timerId)
            self.emit(QtCore.SIGNAL("protocolTimeout()"))
            self.killTimer(self.timerId)
            self.timerId = 0

    def setTimeout(self, value):
        '''Set the timout timer to the given value'''
        if self.timerId:
            self._killTimer(self.timerId)
            self.timerId = 0

        if self.timeout:
            self.connect(socket, QtCore.SIGNAL("readyRead()"), self._readyRead)
            self.connect(socket, QtCore.SIGNAL("bytesWritten(qint64)"), self._bytesWritten)

    def stopTimeout(self):
        self.setTimeout(0)

    def restartTimeout(self):
        '''Kill the current timer and restart'''
        if self.timerId:
            self._killTimer(self.timerId)

        self.timerId = self.startTimer(self.timeout)

class State:
    ReadingHeader, ReadingBody, WaitingReply, SendingReply, Done = range(5)

class HTTPServer(Protocol):
    def __init__(self, socket, timeout=5000, parent=None):
        super(HTTPServer, self).__init__(socket, timeout)
        self.socket            = socket

        self.requestBody       = QtCore.QByteArray()
        self.requestHeader     = QtNetwork.QHttpRequestHeader()
        self.requestHeaderBody = QtCore.QString()

        self.connect(self.socket, QtCore.SIGNAL("readyRead()"), self._readyRead)
        self.connect(self.socket, QtCore.SIGNAL("bytesWritten(qint64)"), self._bytesWritten)

        if self.socket.bytesAvailable() > 0:
            print "HERE!!!"
            self.readyRead()

    def readyReady(self):
        logger.netserver("Attmempting to read from socket")
        if not self.socket.bytesAvailable():
            return

        state = self.socket.state()

        if state == State.ReadingHeader:
            logger.netserver("State: Reading header")
            if self.readRequestHeader() and self.requestContainsBody():
                state = State.ReadingBody
            else:
                state = State.WaitingReply
                self.emit(
                            QtCore.SIGNAL("requestReceived"),
                            (self.requestHeader, self.requestBody)
                        )

        elif state == State.ReadingBody:
            logger.netserver("State: Reading body")
            state = State.WaitingReply
            self.emit(
                        QtCore.SIGNAL("requestReceived"),
                        (self.requestHeader, self.requestBody)
                    )

        elif state == State.WaitingReply:
            logger.netserver("State: Waiting Reply")
            self.emit(QtCore.SIGNAL("parseError"))
        elif state == State.SendingReply:
            logger.netserver("State: Sending Reply")
            self.emit(QtCore.SIGNAL("parseError"))
        elif state == State.Done:
            logger.netserver("State: Done")
            self.emit(QtCore.SIGNAL("parseError"))

    def readRequestBody(self):
        bytesToRead = int(self.requestHeader.contentLength()) - int(self.requestBody.size())
        if bytesToRead > self.socket.bytesAvailable():
            bytesToRead = self.socket.bytesAvailable()

        self.requestBody.append(self.socket.read(bytesToRead))

        if self.requestBody.size() == int(self.requestHeader.contentLength()):
            return True
        else:
            return False

    def requestContainsBody(self):
        return self.requestHeader.hasContentLength()

    def readRequestHeader(self):
        logger.netserver("reading request header")
        win     = "\r\n"
        nix     = "\r"
        winEnd  = QtCore.QByteArray(len(win), win)
        nixEnd  = QtCore.QByteArray(len(nix), nix)

        while socket.canReadLine():
            line = socket.readLine()
            if line == winEnd or line == nixEnd or line.isEmpty():
                break
            self.requestHeaderBody.append(line)

        self.requestHeader = QtNetwork.QHttpRequestHeader(request)
        self.requestHeaderBody.clear()
        self.requestBody.clear()

        if self.requestHeader.isValid():
            logger.debug("Header: %s" % self.requestHeader.toString())
            return True

        logger.error("Invalid request header: %s" % self.requestHeader.toString())
        self.emit(QtCore.SIGNAL("parseError()"))
        return False

class RPCServerThread(QtCore.QThread):
    '''
    Queue server thread spawned upon every incoming connection to
    prevent collisions.
    '''
    def __init__(self, socketId, parent=None):
        super(RPCServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent   = parent

    def run(self):
        logger.debug("Thread started")
        socket = QtNetwork.QTcpSocket()

        if not socket.setSocketDescriptor(self.socketId):
            self.emit(QtCore.SIGNAL("error(int)"), socket.error())
            logger.error("Socket Error: %s" % socket.error())
            return

        http = HTTPServer(socket)
        print socket.bytesAvailable()
        #assert socket.state() == QtNetwork.QAbstractSocket.ConnectedState
        self.connect(socket, QtCore.SIGNAL("disconnected()"), self.disconnected)
        self.connect(http, QtCore.SIGNAL("protocolTimeout"), self.protocolTimeout)
        self.connect(http, QtCore.SIGNAL("parseError"), self.parseError)
        self.connect(http, QtCore.SIGNAL("requestReceived"), self.requestReceived)
        self.connect(http, QtCore.SIGNAL("replySent"), self.replySent)

    def disconnected(self):
        logger.netserver("disconnected")

    def protocolTimeout(self):
        logger.netserver("Protocol has timed out")

    def parseError(self):
        logger.error("parse error!")

    def requestReceived(self, data):
        logger.netserver("requestReceived: %s" % data)

    def replySent(self, reply):
        logger.netserver("replySent: %s" % reply)


class RPCServer(QtNetwork.QTcpServer):
    '''
    Main remote proceduce call server class.  Used to receieve
    calls from client and register new functions on the local server.
    '''
    def __init__(self, cert, key, password, parent=None):
        super(RPCServer, self).__init__(parent)
        logger.netserver("Server setup")

    @property
    def name(self):
        return "RPC"

    def incomingConnection(self, socketId):
        '''
        When an incoming connection is spawned create a thread
        and send the new connection to it
        '''
        logger.netserver("Incoming Connection")
        self.thread = RPCServerThread(socketId, parent=self)
        self.connect(
                        self.thread,
                        QtCore.SIGNAL("finished()"),
                        self.thread,
                        QtCore.SLOT("deleteLater()")
                    )
        self.thread.start()


    def registerSlot(self, target, slot, path=None):
        pass

class TestServer(QtCore.QObject):
    def __init__(self, parent=None):
        super(TestServer, self).__init__(parent)
        self.server = RPCServer('cert', 'key', 'password')

        if self.server.listen(QtNetwork.QHostAddress("127.0.0.1"), 5050):
            self.server.registerSlot(self, QtCore.SLOT("echo(QVariant)"))

    def echo(self, text):
        return text



if __name__ == '__main__':
    logger.info("Starting: %i" % os.getpid())
    app    = QtCore.QCoreApplication(sys.argv)
    server = RPCServer('cert', 'key', 'password')
    if server.listen(QtNetwork.QHostAddress("127.0.0.1"), 5050):
        logger.netserver("%s Server Running on port %i" % (server.name, server.serverPort()))
    app.exec_()