#!/usr/bin/env python
# UDP Inetd Server - Chapter 3 - inetdudpserver.py
import socket, time, sys

s = socket.fromfd(sys.stdin.fileno(), socket.AF_INET, socket.SOCK_DGRAM)
message, address = s.recvfrom(8192)
s.connect(address)
for i in range(10):
    s.send("Reply %d: %s" % (i + 1, message))
    time.sleep(2)
s.send("OK, I'm done sending replies.\n")
