#!/usr/bin/env python
# Connect Example with IPv6 Awareness - Chapter 5 - ipv6connect.py

import socket, sys

def getaddrinfo_pref(host, port, socktype, familypreference = socket.AF_INET):
    """Given a host, port, and socktype (usually socket.SOCK_STREAM or
    socket.SOCK_DGRAM), looks up information with both IPv4 and IPv6.  If
    information is found corresponding to the familypreference, it is returned.
    Otherwise, any information found is returned.  The familypreference
    defaults to IPv4 (socket.AF_INET) but you could also set it to
    socket.AF_INET6 for IPv6.

    The return value is the appropriate tuple returned from
    socket.getaddrinfo()."""
    results = socket.getaddrinfo(host, port, 0, socktype)
    for result in results:
        if result[0] == familypreference:
            return result
    return results[0]

host = sys.argv[1]
port = 'http'

c = getaddrinfo_pref(host, port, socket.SOCK_STREAM)
print "Connecting to", c[4]
s = socket.socket(c[0], c[1])
s.connect(c[4])
s.sendall("HEAD / HTTP/1.0\n\n")

while 1:
    buf = s.recv(4096)
    if not len(buf):
        break
    sys.stdout.write(buf)
