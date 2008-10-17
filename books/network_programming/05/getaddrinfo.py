#!/usr/bin/env python
# getaddrinfo() display - Chapter 5 - getaddrinfo.py
# Give a host and a port on the command line

import socket, sys
host, port = sys.argv[1:]

# Look up the given data
results = socket.getaddrinfo(host, port, 0, socket.SOCK_STREAM)

# We may get multiple results back.  Display each one.
for result in results:
    # Display a separator line to visually segment one result from the next.
    print "-" * 60

    # Print whether we got back an IPv4 or IPv6 result.
    if result[0] == socket.AF_INET:
        print "Family: AF_INET"
    elif result[0] == socket.AF_INET6:
        print "Family: AF_INET6"
    else:
        # It's not IPv4 or IPv6, so we don't know about it.  Just print
        # out its protocol number.
        print "Family:", result[0]

    # Indicate whether it's a stream (TCP) or datagram (UDP) result.
    if result[1] == socket.SOCK_STREAM:
        print "Socket Type: SOCK_STREAM"
    elif result[1] == socket.SOCK_DGRAM:
        print "Socket Type: SOCK_DGRAM"

    # Display the final bits of information from getaddrinfo()
        
    print "Protocol:", result[2]
    print "Canonical Name:", result[3]
    print "Socket Address:", result[4]
