#!/usr/bin/python
# Broadcast Sender - Chapter 5 - bcastsender.py

import socket, sys
dest = ('<broadcast>', 51423)
hosts = []

try:
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    s.settimeout(1)
    s.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
    s.sendto("Hello", dest)
    print "Looking for replies; press Ctrl-C to stop."
    while 1:
        (buf, address) = s.recvfrom(2048)
        if not len(buf):
            break
            
        hosts.append(address)
        #print "Received from %s: %s" % (address, buf)
        
except socket.timeout:
    pass
finally:
    print hosts
