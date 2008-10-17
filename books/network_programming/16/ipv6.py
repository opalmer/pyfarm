#!/usr/bin/env python
# SocketServer IPv6 Example - Chapter 16 - ipv6.py

from SocketServer import ThreadingMixIn, TCPServer, StreamRequestHandler
import time, socket

class TimeRequestHandler(StreamRequestHandler):
    def handle(self):
        print "Connection from", self.client_address
        req = self.rfile.readline().strip()
        if req == "asctime":
            result = time.asctime()
        elif req == "seconds":
            result = str(int(time.time()))
        elif req == "rfc822":
            result = time.strftime("%a, %d %b %Y %H:%M:%S +0000",
                     time.gmtime())
        else:
            result = """Unhandled request.  Send a line with one of the
following words:

asctime -- for human-readable time
seconds -- seconds since the Unix Epoch
rfc822  -- date/time in format used for mail and news posts"""
        self.wfile.write(result + "\n")

class TimeServer(ThreadingMixIn, TCPServer):
    allow_reuse_address = 1
    address_family = socket.AF_INET6

serveraddr = ('', 8765)
srvr = TimeServer(serveraddr, TimeRequestHandler)
srvr.serve_forever()
