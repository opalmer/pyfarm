#!/usr/bin/env python
# SimpleXMLRPCServer Basic Example - Chapter 17 - simple.py
# This program requires Python 2.3 or above

from SimpleXMLRPCServer import SimpleXMLRPCServer, SimpleXMLRPCRequestHandler
from SocketServer import ForkingMixIn

class Math:
    def pow(self, x, y):
        """Returns x raised to the yth power; that is, x ^ y.
        
        x and y may be integers or floating-point values."""
        return x ** y

    def hex(self, x):
        """Returns a string holding a hexadecimal representation of
        the integer x."""
        return "%x" % x

class ForkingServer(ForkingMixIn, SimpleXMLRPCServer):
    pass

serveraddr = ('', 8765)
srvr = ForkingServer(serveraddr, SimpleXMLRPCRequestHandler)
srvr.register_instance(Math())
srvr.register_introspection_functions()
srvr.serve_forever()
