#!/usr/bin/env python
# Basic gethostbyaddr() example - Chapter 4 - gethostbyaddr-basic.py
# This program performs a reverse lookup on the IP address given
# on the command line

import sys, socket

try:
    # Perform the lokoup
    result = socket.gethostbyaddr(sys.argv[1])

    # Display the looked-up hostname
    print "Primary hostname:"
    print "  " + result[0]

    # Display the list of available addresses that is also returned
    print "\nAddresses:"
    for item in result[2]:
        print "  " + item

except socket.herror, e:
    print "Couldn't look up name:", e

