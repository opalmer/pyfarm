#!/usr/bin/env python

import re
import sys
from PyQt4 import QtXml

def elementIs(element, value):
    '''Determine if the requested element matches the given value'''
    if element.tagName() != value:
        print "ERROR: element is not %s instead it's '%s'" % (value, element.tagName())
        return False
    return True

def main(socket):
    #data       = str(socket.readAll().trimmed())
    data       = socket
    source     = re.match(r""".+ \d+(:?\r\n)+(.+)""", data, re.DOTALL).group(2)
    doc        = QtXml.QDomDocument()
    setContent = doc.setContent(source)

    if not setContent[0]:
        args = (str(setContent[1]), setContent[1], setContent[2])
        print "Error parsing xml from rpc request!!!"
        print "...%s, line %i, column %i" % args
        #socket.close()
        return None

    child = doc.firstChild()
    while not child.isNull():
        element = child.toElement()
        if not element.isNull():
            break

        child = child.nextSibling()

    if not elementIs(element, "methodCall"):
        #socket.close()
        return None
    childElement = element.firstChild().toElement()

    if not elementIs(childElement, "methodName"):
        #socket.close()
        return None

    methodName = childElement.firstChild().toText().data().toLatin1()
    if methodName.isEmpty():
        print "ERROR: Method name is empty"
        #socket.close()
        return None

    print "Method Name: %s" % methodName

    # iterate over parms
    print element.tagName()
    #element = element.nextSibling().toElement()
    #while not element.isNull():
        #print "HERE"

    # TODO: Write Marshall object to do conversion:
    # response = Response(socket)
    # response.method ('echo')
    # response.inputs (list of inputs and their correct type)
    # Converting to a DICTIONARY first may be the best start

if __name__ == '__main__':
    xml = "ST /RPC2 HTTP/1.0\r\nHost: 127.0.0.1:54000\r\nUser-Agent: xmlrpclib.py/1.0.1 (by www.pythonware.com)\r\nContent-Type: text/xml\r\nContent-Length: 152\r\n\r\n<?xml version='1.0'?>\n<methodCall>\n<methodName>echo</methodName>\n<params>\n<param>\n<value><string>test</string></value>\n</param>\n</params>\n</methodCall>"
    main(xml)