'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008 (revised in July 2010)
PURPOSE: Group of classes dedicated to the collection and monitoring
of queue information.

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
import xmlrpclib
import xml.etree.ElementTree

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import logger, system, net

UNIT16         = 8
STREAM_VERSION = net.dataStream()
logger         = logger.Logger()
types          = []

def rpcResponse(source, typeName, value):
    '''
    Based on the source object arguments formulate and return a response
    '''
    reply  = QtCore.QByteArray("<?xml version='1.0'?>")
    reply.append("<methodResponse><params><param><value>")
    reply.append("<string>%s</string>" % str(value))
    reply.append("</value></param></params></methodResponse>")

    return reply

def rpcResponseB(results):
    '''
    Based on the source object arguments formulate and return a response
    '''
    reply = QtCore.QByteArray()
    dump  = xmlrpclib.dumps((results, ), methodresponse=True)
    for line in dump:
        reply.append(line.strip())

    #reply.append(dump)

    return reply

class RPCServerThread(QtCore.QThread):
    def __init__(self, socketId, parent=None):
        super(RPCServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent   = parent

    def _getData(self, socket):
        '''
        Parse the incoming request from the socket and return the method
        call and its input(s)
        '''
        return xmlrpclib.loads(socket.readAll().trimmed())

    def run(self):
        logger.debug("Thread started")
        socket = QtNetwork.QTcpSocket()

        if not socket.setSocketDescriptor(self.socketId):
            logger.error("SOCKET ERROR: %s" % str(socket.error()))
            self.emit(QtCore.SIGNAL("error(int)"), socket.error())
            return

        while socket.state() == QtNetwork.QAbstractSocket.ConnectedState:
            logger.info("Connected")
            nextBlockSize = 0
            stream        = QtCore.QDataStream(socket) # create a data stream
            stream.setVersion(STREAM_VERSION)

            while True:
                socket.waitForReadyRead(-1)

                ## load next block size with the total size of the stream
                if socket.bytesAvailable() >= UNIT16:
                    nextBlockSize  = stream.readUInt16()
                    data           = self._getData(socket)

                    results = ''
                    self.sendReply(socket, data[0])
                    break

            if socket.bytesAvailable() < nextBlockSize:
                socket.close()

            socket.waitForDisconnected()

    def sendReply(self, socket, response):
        reply = rpcResponse('test', 'test', response)
        replyB = rpcResponseB(response)
        print len(reply),reply
        print len(replyB),replyB
        socket.write(replyB)
        socket.close()

class RPCServer(QtNetwork.QTcpServer):
    def __init__(self, parent=None):
        super(RPCServer, self).__init__(parent)

    def incomingConnection(self, socketId):
        logger.netserver("Incoming Connection")
        self.thread = RPCServerThread(socketId, parent=self)
        self.connect(
                        self.thread, QtCore.SIGNAL("finished()"),
                        self.thread, QtCore.SLOT("deleteLater()")
                    )
        self.thread.start()

if __name__ == '__main__':
    logger.info("Starting: %i" % os.getpid())
    app    = QtCore.QCoreApplication(sys.argv)
    server = RPCServer()

    if server.listen(QtNetwork.QHostAddress("127.0.0.1"), 54000):
        logger.netserver("RPC Server Running on port 54000")

    sys.exit(app.exec_())