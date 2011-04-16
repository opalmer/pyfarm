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

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from xmlrpc import Deserialize
from lib import logger, system, net

UNIT16         = 8
STREAM_VERSION = net.dataStream()
logger         = logger.Logger()
types          = []

class RPCServerThread(QtCore.QThread):
    '''
    Server thread than handles all processing of a rpc connection.  This
    object should be passed to the QTcpServer prior to starting the server:

    server = RPCSErver(RPCServerThread)
    '''
    def __init__(self, socketId, parent=None):
        super(RPCServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent   = parent
        self.socket   = None
        self.peer     = None
        self.data     = None

    def response(self):
        '''
        This method is used to override self.response before sending our
        response to the peer and should be overridden by any subclass.
        '''
        return True

    def sendFault(self, fault, faultCode):
        '''Send a fault code to the remote host'''
        self.sendReply(fault=fault, faultCode=faultCode)

    def sendReply(self, fault=None, faultCode=-1):
        '''
        Send our response and close the socket.  Be sure that before we
        send out reply we construct a proper response using xmlrpclib.dumps.
        This method can also send fault objects when given a fault string.
        '''
        if not fault:
            reply  = (self.response(), )
            method = self.data.method
            dump   = xmlrpclib.dumps(
                                        reply,
                                        methodname=method, methodresponse=True
                                    )
        # if we have fault argumen
        else:
            fault = xmlrpclib.Fault(fault, faultCode)
            dump  = xmlrpclib.dumps(fault)

        # send the rpc dump and close the connection
        self.socket.write(dump)
        self.socket.close()

    def run(self):
        '''
        Main processing of thread object, this method should not be
        overriden
        '''
        self.socket = QtNetwork.QTcpSocket()

        if not self.socket.setSocketDescriptor(self.socketId):
            error = str(self.socket.error())
            logger.error("Error Setting Socket Descriptor: %s" % error)
            self.emit(QtCore.SIGNAL("error(int)"), self.socket.error())
            self.sendFault("Error setting socket descriptor", 1)
            return

        self.peer = str(self.socket.peerAddress().toString())
        while self.socket.state() == QtNetwork.QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream        = QtCore.QDataStream(self.socket)
            stream.setVersion(STREAM_VERSION)

            while True:
                self.socket.waitForReadyRead(-1)

                if self.socket.bytesAvailable() >= UNIT16:
                    nextBlockSize = stream.readUInt16()
                    self.data     = Deserialize(self.socket)
                    logger.rpccall("%s -> %s%s" % (
                                                self.peer, self.data.method,
                                                self.data.parms
                                                )
                                    )
                    self.sendReply()
                    break

            if self.socket.bytesAvailable() < nextBlockSize:
                if not self.socket.waitForDisconnected():
                    error = str(self.socket.error())
                    self.sendFault("Error while disconnecting", 2)


class RPCServer(QtNetwork.QTcpServer):
    def __init__(self, rpcThread=None, parent=None):
        super(RPCServer, self).__init__(parent)
        self.rpcThread = rpcThread

    def incomingConnection(self, socketId):
        self.thread = self.rpcThread(socketId, parent=self)
        self.connect(
                        self.thread, QtCore.SIGNAL("finished()"),
                        self.thread, QtCore.SLOT("deleteLater()")
                    )
        self.thread.start()

if __name__ == '__main__':
    logger.info("Starting: %i" % os.getpid())
    app    = QtCore.QCoreApplication(sys.argv)
    server = RPCServer(RPCServerThread)

    if server.listen(QtNetwork.QHostAddress("127.0.0.1"), 54000):
        logger.netserver("RPC Server Running on port 54000")

    sys.exit(app.exec_())