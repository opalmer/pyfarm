#!/usr/bin/env python
# Basic OpenSSL example - Chapter 15 - osslbasic.py

import socket, sys
from OpenSSL import SSL

# Create SSL context object
ctx = SSL.Context(SSL.SSLv23_METHOD)

print "Creating socket...",
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
print "done."

# Create SSL connection object
ssl = SSL.Connection(ctx, s)

print "Establishing SSL...",
ssl.connect(('www.openssl.org', 443))
print "done."

print "Requesting document...",
ssl.sendall("GET / HTTP/1.0\r\n\r\n")
print "done."

while 1:
    try:
        buf = ssl.recv(4096)
    except SSL.ZeroReturnError:
        break
    sys.stdout.write(buf)

ssl.close()

