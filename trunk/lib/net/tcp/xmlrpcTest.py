#!/usr/bin/env python

import re
import sys
import types
import xmlrpclib
from PyQt4 import QtCore
import xml.etree.cElementTree

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
        if type(socket) == QtCore.QByteArray:
            self.source = socket.readAll()

            if socket.isEmpty():
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