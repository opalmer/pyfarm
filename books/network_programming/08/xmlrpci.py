#!/usr/bin/env python
# XML-RPC Introspection Client - Chapter 8 - xmlrpci.py

import xmlrpclib, sys

url = 'http://www.oreillynet.com/meerkat/xml-rpc/server.php'
s = xmlrpclib.ServerProxy(url)

print "Gathering available methods..."
methods = s.system.listMethods()

while 1:
    print "\n\nAvailable Methods:"
    for i in range(len(methods)):
        print "%2d: %s" % (i + 1, methods[i])
    selection = raw_input("Select one (q to quit): ")
    if selection == 'q':
        break
    item = int(selection) - 1
    print "\n*********"
    print "Details for %s\n" % methods[item]
    
    for sig in s.system.methodSignature(methods[item]):
        print "Args: %s; Returns: %s" % \
                (", ".join(sig[1:]), sig[0])
    print "Help:", s.system.methodHelp(methods[item])

