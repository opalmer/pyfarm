#!/usr/bin/env python
# Basic getaddrinfo() list example - Chapter 4 - getaddrinfo-list.py
# Takes a host name on the command like and prints all resulting
# matches for it.

import sys, socket

# Obtain results for socket.SOCK_STREAM (TCP) only, and put a list
# of them into the "result" variable.
result = socket.getaddrinfo(sys.argv[1], None, 0, socket.SOCK_STREAM)

counter = 0
for item in result:
    # Print out the address tuple for each item
    print "%-2d: %s" % (counter, item[4])
    counter += 1
