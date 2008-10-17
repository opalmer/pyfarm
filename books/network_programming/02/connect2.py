#!/usr/bin/env python
# Revised Connection Example - Chapter 2 - connect.py

import socket

print "Creating socket...",
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
print "done."

print "Looking up port number...",
port = socket.getservbyname('http', 'tcp')
print "done."

print "Connecting to remote host on port %d..." % port,
s.connect(("www.google.com", port))
print "done."
