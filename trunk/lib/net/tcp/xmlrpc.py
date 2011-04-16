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
import re
import sys
import types
import xmlrpclib
from PyQt4 import QtCore
import xml.etree.cElementTree

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import logger, system, net

UNIT16         = 8
STREAM_VERSION = net.dataStream()
logger         = logger.Logger('test', 'test')
loads          = xmlrpclib.loads

def dumps(method, values):
    '''
    Return a valid dump response for transmission back to an xmlrpc client
    '''
    return xmlrpclib.dumps((values, ), method, methodresponse=True)

class SerializationFailure(Exception):
    def __repr__(self, error):
        return "Failed to Serialize Data: %s" % error.lower()


class InvalidRPC(Exception):
    def __repr__(self, error):
        return "Invalid Data: %s" % error.lower()


class Serialization(QtCore.QObject):
    '''
    Base class holding generic functions for serialization.  This class
    also emits a signal (method) upon finding the method name

    @param socket: String or byte array to parse xml from
    '''
    def __init__(self, socket, parent=None):
        super(Serialization, self).__init__(parent)
        if type(socket) == QtNetwork.QTcpSocket:
            self.source = socket.readAll()

            if self.source.isEmpty():
                raise InvalidRPC("socket byte array is empty")

        elif type(socket) in types.StringTypes:
            self.source = socket

            if not self.source:
                raise InvalidRPC("source string is empty")

        else:
            error = "cannot serialize %s objects" % type(socket)
            raise SerializationFailure(error)

        self.cTree  = xml.etree.cElementTree.fromstring(self._getXml(self.source))

        for child in self.cTree.getchildren():
            # set the mothod name
            if child.tag == "methodName":
                self.method = child.text
                self.emit(QtCore.SIGNAL("method"), child.text)

            # establish the parameters
            elif child.tag == "params":
                self.inputs = child

    def _getXml(self, source):
        '''
        Parse the rpc request and return the xml structure for processing.

        @param source: The full rpc structure to return the xml from
        @type  source: C{str}
        '''
        match = re.match(r""".+ \d+(:?\r\n)+(.+)""", source, re.DOTALL)

        if not match:
            raise SerializationFailure("failed to match xml header")

        return match.group(2)


class Deserialize(Serialization):
    def __init__(self, socket, parent=None):
        super(Deserialize, self).__init__(socket, parent)
        self.parms = []

        # iterate over all parameters and create a list of inputs
        for inputElement in self.getValues(self.inputs):
            for parm in inputElement.getchildren():
                self.parms.append(self.parsedType(parm))

        self.parms = tuple(self.parms)

    def parsedType(self, element):
        '''
        Return the correct type for the given element.  In the interest of
        security, eval() will not be used in place of a list lookup for
        boolean objects.

        @param element: The element to pull typeName and value from
        @type  element: xml.etree.cElementTree.Element
        '''
        typeName    = element.tag
        value       = element.text
        if typeName == "struct":   return self.parsedDict(element)
        elif typeName == "string": return str(value)
        elif typeName == "double": return float(value)
        elif typeName == "int":    return int(value)
        elif typeName == "boolean":
            if value in ("True", "1.0", "1"):
                return True
            return False

        else:
            raise TypeError("%s has not been mapped yet" % typeName)

    def parsedDict(self, element):
        '''
        Recursivly parse a struct entry and return a dictionary

        @param element: The struct element to return a dictionary for
        @type  element: xml.etree.cElementTree.Element
        '''
        output = {}
        for member in element.getchildren():
            for entry in member.getchildren():
                # set the next keyname
                if entry.tag == "name":
                    key = entry.text

                else:
                    for value in entry.getchildren():
                        output[key] = self.parsedType(value)

        return output

    def getValues(self, parameters):
        '''Return all value elements from the given parameters'''
        for child in parameters.getchildren():
            for value in child.getchildren():
                yield value


class RPCServerThread(QtCore.QThread):
    '''
    Server thread than handles all processing of a rpc connection.  This
    object should be passed to the QTcpServer prior to starting the server:

    server = RPCServer(RPCServerThread)
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
        self.threadClass = threadClass

    def incomingConnection(self, socketId):
        self.thread = self.threadClass(socketId, parent=self)
        self.connect(
                        self.thread, QtCore.SIGNAL("finished()"),
                        self.thread, QtCore.SLOT("deleteLater()")
                    )
        self.thread.start()

if __name__ == '__main__':
    import xmlrpclib
    xmlSource  = "POST /RPC2 HTTP/1.0\r\nHost: 127.0.0.1:54000\r\nUser-Agent: xmlrpclib.py/1.0.1 (by www.pythonware.com)\r\nContent-Type: text/xml\r\nContent-Length: 336\r\n\r\n<?xml version='1.0'?>\n<methodCall>\n<methodName>echo</methodName>\n<params>\n<param>\n<value><struct>\n<member>\n<name>key</name>\n<value><string>value</string></value>\n</member>\n</struct></value>\n</param>\n<param>\n<value><double>2.0</double></value>\n</param>\n<param>\n<value><string>thirdInput</string></value>\n</param>\n</params>\n</methodCall>\n"
    xmlSourceB = "ST /RPC2 HTTP/1.0\r\nHost: 127.0.0.1:54000\r\nUser-Agent: xmlrpclib.py/1.0.1 (by www.pythonware.com)\r\nContent-Type: text/xml\r\nContent-Length: 1004\r\n\r\n<?xml version='1.0'?>\n<methodCall>\n<methodName>echo</methodName>\n<params>\n<param>\n<value><struct>\n<member>\n<name>dictA</name>\n<value><struct>\n<member>\n<name>entryAA</name>\n<value><boolean>1</boolean></value>\n</member>\n<member>\n<name>entryAB</name>\n<value><int>1</int></value>\n</member>\n<member>\n<name>entryAC</name>\n<value><double>1.0</double></value>\n</member>\n<member>\n<name>dictAD</name>\n<value><struct>\n<member>\n<name>itemB</name>\n<value><string>hi there</string></value>\n</member>\n<member>\n<name>itemA</name>\n<value><boolean>1</boolean></value>\n</member>\n<member>\n<name>emptyDict</name>\n<value><struct>\n</struct></value>\n</member>\n</struct></value>\n</member>\n</struct></value>\n</member>\n<member>\n<name>strA</name>\n<value><string>hello</string></value>\n</member>\n<member>\n<name>intA</name>\n<value><double>1.0</double></value>\n</member>\n</struct></value>\n</param>\n<param>\n<value><double>2.0</double></value>\n</param>\n<param>\n<value><string>thirdInput</string></value>\n</param>\n</params>\n</methodCall>\n"
    xmlSourceC = "ml\r\nContent-Length: 1004\r\n\r\n<?xml version='1.0'?>\n<methodCall>\n<methodName>echo</methodName>\n<params>\n<param>\n<value><struct>\n<member>\n<name>dictA</name>\n<value><struct>\n<member>\n<name>entryAA</name>\n<value><boolean>1</boolean></value>\n</member>\n<member>\n<name>entryAB</name>\n<value><int>1</int></value>\n</member>\n<member>\n<name>entryAC</name>\n<value><double>1.0</double></value>\n</member>\n<member>\n<name>dictAD</name>\n<value><struct>\n<member>\n<name>itemB</name>\n<value><string>hi there</string></value>\n</member>\n<member>\n<name>itemA</name>\n<value><boolean>1</boolean></value>\n</member>\n<member>\n<name>emptyDict</name>\n<value><struct>\n</struct></value>\n</member>\n</struct></value>\n</member>\n</struct></value>\n</member>\n<member>\n<name>strA</name>\n<value><string>hello</string></value>\n</member>\n<member>\n<name>intA</name>\n<value><double>1.0</double></value>\n</member>\n</struct></value>\n</param>\n<param>\n<value><double>2.0</double></value>\n</param>\n<param>\n<value><string>thirdInput</string></value>\n</param>\n</params>\n</methodCall>\n"

    i = 1
    for source in (xmlSource, xmlSourceB, xmlSourceC):
        print "Testing Source %i..." % i
        xmlToObjs = Deserialize(source)
        objsToXml = dumps(xmlToObjs.method, xmlToObjs.parms)
        xmlToLoad = xmlrpclib.loads(objsToXml)
        print "...method name match: %s" % (xmlToObjs.method == xmlToLoad[1])
        print "....input parm match:  %s" % (xmlToObjs.parms == tuple(xmlToLoad[0][0]))
        i += 1