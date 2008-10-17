#!/usr/bin/env python
# Basic getaddrinfo() not quite right list example - Chapter 4
# getaddrinfo-list-broken.py
# Takes a host name on the command line and prints all resulting
# matches for it.  Broken; a given name may occur multiple times.

import sys, socket

# Put the list of results into the "result" variable.
result = socket.getaddrinfo(sys.argv[1], None)

counter = 0
for item in result:
    # Print out the address tuple for each item
    print "%-2d: %s" % (counter, item[4])
    counter += 1
