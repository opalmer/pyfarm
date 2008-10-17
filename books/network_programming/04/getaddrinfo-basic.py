#!/usr/bin/env python
# Basic getaddrinfo() basic example - Chapter 4 - getaddrinfo-basic.py

import sys, socket

result = socket.getaddrinfo(sys.argv[1], None)
print result[0][4]
