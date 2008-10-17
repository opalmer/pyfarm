#!/usr/bin/env python
# SimpleXMLRPCServer Example with functions - Chapter 17 - func.py
# This program requires Python 2.3 or above

from SimpleXMLRPCServer import SimpleXMLRPCServer, SimpleXMLRPCRequestHandler
from SocketServer import ForkingMixIn

class Math:
    def pow(self, x, y):
        """Returns x raised to the yth power; that is, x ^ y.
        
        x and y may be integers or floating-point values."""
        return pow(x, y)

    def hex(self, x):
        """Returns a string holding a hexadecimal representation of
        the integer x."""
        return "%x" % x

    def sortlist(self, l):
        """Sorts the items in l."""
        l = list(l)
        l.sort()
        return l

class ForkingServer(ForkingMixIn, SimpleXMLRPCServer):
    pass

serveraddr = ('', 8765)
srvr = ForkingServer(serveraddr, SimpleXMLRPCRequestHandler)
srvr.register_instance(Math())
srvr.register_introspection_functions()
srvr.register_function(int)
srvr.register_function(list.sort)      # Won't work!
srvr.serve_forever()
