#!/usr/bin/env python
# Basic HTTP CGI Server Example with forking -- Chapter 17

from BaseHTTPServer import HTTPServer
from CGIHTTPServer import CGIHTTPRequestHandler
from SocketServer import ForkingMixIn

class ForkingServer(ForkingMixIn, HTTPServer):
    pass

serveraddr = ('', 8765)
srvr = ForkingServer(serveraddr, CGIHTTPRequestHandler)
srvr.serve_forever()
