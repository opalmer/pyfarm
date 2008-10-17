#!/usr/bin/env python
# Basic HTTP Server Example with threading - Chapter 16
# simplehttpthread.py

from BaseHTTPServer import HTTPServer
from SimpleHTTPServer import SimpleHTTPRequestHandler
from SocketServer import ThreadingMixIn

class ThreadingServer(ThreadingMixIn, HTTPServer):
    pass

serveraddr = ('', 8765)
srvr = ThreadingServer(serveraddr, SimpleHTTPRequestHandler)
srvr.serve_forever()
