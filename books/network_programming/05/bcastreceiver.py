#!/usr/bin/env python
# UDP Broadcast Server - Chapter 5 - bcastreceiver.py
import socket, traceback

host = ''                               # Bind to all interfaces
port = 51423

s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
s.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
s.bind((host, port))

while 1:
    try:
        message, address = s.recvfrom(8192)
        print "Got data from", address
        # Acknowledge it.
        s.sendto("I am here", address)
    except (KeyboardInterrupt, SystemExit):
        raise
    except:
        traceback.print_exc()

