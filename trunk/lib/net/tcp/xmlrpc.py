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
logger         = logger.Logger()

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


class RPCServer(QtNetwork.QTcpServer):
    '''
    Main remote proceduce call server class.  Used to receieve
    calls from client and register new functions on the local server.


    '''
    def __init__(self, cert, key, password, parent=None):
        super(RPCServer, self).__init__(parent)
        self.cert     = QtCore.QString(cert):
        self.key      = QtCore.QString(key)
        self.password = QtCore.QByteArray(len(password), password)
        logger.netserver("Server Running")

    def incomingConnection(self, socketId):
        '''
        When an incoming connection is spawned create a thread
        and send the new connection to it
        '''
        logger.netserver("Incoming Connection")
        self.thread = RPCServerThread(socketId, main=self.main, parent=self)
        self.connect(
                        self.thread,
                        QtCore.SIGNAL("finished()"),
                        self.thread,
                        QtCore.SLOT("deleteLater()")
                    )
        self.thread.start()

    def registerSlot(self, receiver, slot, path="/RPC2/"):
        '''Register a slot to return rpc calls from'''
        pass

    def echo(self, value):
        '''Simple echo call to return the requsted value (mainly for testing'''
        return value

    def deferredEcho(self, value): pass

    # these functions may need to go under the handler thread
    def slotSocketDisconnected(self): pass
    def slotProtocolTimeout(self, protocol): pass # @type protocol: xmlrpc.Protocol
    def slotParseError(self, http): pass # @type xmlrpc.http: HttpServer
    def slotRequestReceieved(self, http, header, body):
        '''
        Handler for request

        @type http: xmlrpc.HttpServer
        @type header: QtNetwork.QHttpRequestHeader
        @type body: QtCore.QByteArray
        '''
        pass

    def slotReplySent(self, http): pass # @type xmlrpc.HttpServer

    # destroy the qobject when it does, this function
    # may only exist as part of the original C++
    # design
    def slotReceieverDestroyed(self, qobj): pass