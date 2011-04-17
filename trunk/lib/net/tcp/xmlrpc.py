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
import inspect
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
        #elif typeName == "dateTime.iso8601":
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


class BaseServerThread(QtCore.QThread):
    '''
    Server thread than handles all processing of a rpc connection.  This
    object should be passed to the QTcpServer prior to starting the server:

    server = RPCServer(RPCServerThread)
    '''
    def __init__(self, socketId, parent=None):
        super(BaseServerThread, self).__init__(parent)
        self.socketId = socketId
        self.parent   = parent
        self.socket   = None
        self.peer     = None
        self.data     = None

    def _callMethod(self, method, args):
        '''
        Call the requested method while passing arguments, return the
        results of the call

        TODO: Replace the eval statement
        '''
        return eval("self.%s%s" % (method, args))

    def _allMethods(self, parentMethods=False):
        '''Return a list of methods currently attached to the class'''
        methods = []
        for listing in inspect.getmembers(self):
            if inspect.ismethod(listing[1]):
                methods.append(listing[0])
        return methods

    def _methodList(self):
        '''
        Return a list of methods being overridden in this class (methods
        that can be called via rpc)
        '''
        methods = []
        for name in vars(self.__class__).keys():
            if not name.startswith("_"):
                methods.append(name)
        return methods

    def _containsMethod(self, method):
        '''Return True if the class contains the given method'''
        if method in self._methodList():
            return True
        return False

    def _validMethod(self, method):
        '''
        We cannot allow clients to call private methods, check to see if the
        current method being called is considered private.
        '''
        if not method.startswith("_"):
            return True
        return False

    def _validateCall(self, method, args):
        '''
        Return True if the input given by the RPC call from the client matches
        the required input to the given method.
        '''
        method     = getattr(self.__class__, method)
        inspection = list(inspect.getargspec(method))

        # make sure all inspection output are not None
        index = 0
        for entry in inspection:
            if not entry:
                inspection[index] = []
            index += 1

        arguments = len(inspection[0][1:])
        keywords  = len(inspection[3])
        required  = arguments - keywords
        argCount  = len(args)

        if argCount <= arguments and argCount >= required:
            return True
        return False

    def _validateRequest(self):
        '''
        Perform the checks necessary to ensure the method we are attempting
        to call is valid
        '''
        fault      = None
        faultCode  = None
        method     = self.data.method
        methodArgs = self.data.parms

        # make sure the method calls is not private
        if not self._validMethod(method):
            fault     = "Permission denied to private method"
            faultCode = 403
            return fault, faultCode

        # make sure the class contains the method
        if not self._containsMethod(method):
            fault     = "No such method self.%s" % method
            faultCode = 404
            return fault, faultCode

        # ensure our input to the method contains the proper arguments
        if not self._validateCall(method, methodArgs):
            fault     = "Invalid input to self.%s" % method
            faultCode = 400
            return fault, faultCode

        return fault, faultCode

    def sendFault(self, fault, faultCode):
        '''Send a fault code to the remote host'''
        self.sendReply(error=fault, errorCode=faultCode)

    def sendReply(self, error=None, errorCode=-1):
        '''
        Send our response and close the socket.  Be sure that before we
        send out reply we construct a proper response using xmlrpclib.dumps.
        This method can also send fault objects when given a fault string.
        '''
        # if error is currently none, then we need to validate the rpc request
        if not error:
            error, errorCode = self._validateRequest()

        # if the there still is not an error after validation then we
        # can query the results
        if not error:
            reply  = (self._callMethod(self.data.method, self.data.parms), )
            dump   = xmlrpclib.dumps(
                                        reply,
                                        methodname=self.data.method,
                                        methodresponse=True
                                    )
            logger.rpcresult("%s <- %s" % (self.peer, str(reply[0])))

        # if input is passed into fault send a fault string and code
        # back to the client instead
        else:
            fault = xmlrpclib.Fault(error, errorCode)
            dump  = xmlrpclib.dumps(fault)
            logger.rpcerror("%s <- Error %i: %s" % (self.peer, errorCode, error))

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
                    try:
                        self.data = Deserialize(self.socket)

                    except TypeError, error:
                        self.sendFault(str(error), 501)
                        logger.rpcerror("%s: %s" % (self.peer, str(error)))

                    except Exception, error:
                        self.sendFault(str(error), 500)
                        logger.error("Unhandled Exception: %s" % error)

                    else:
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
                    self.sendFault("Error while disconnecting: %s" % error, 500)
                    logger.error("Error disconnecting from %s: %s" % (
                                                                       self.peer,
                                                                       error
                                                                     )
                                                                     )


class BaseServer(QtNetwork.QTcpServer):
    def __init__(self, threadClass=None, parent=None):
        super(BaseServer, self).__init__(parent)
        self.threadClass = threadClass

    def incomingConnection(self, socketId):
        self.thread = self.threadClass(socketId, parent=self)
        self.connect(
                        self.thread, QtCore.SIGNAL("finished()"),
                        self.thread, QtCore.SLOT("deleteLater()")
                    )
        self.thread.start()
