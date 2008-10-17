#!/usr/bin/env python
# Echo Server with Threading - Chapter 21 - echoserver.py
# Compare to echo server in Chapters 3 and 20

import socket, traceback, os, sys
from threading import *

host = ''                               # Bind to all interfaces
port = 51423

def handlechild(clientsock):
    print "New child", currentThread().getName()
    print "Got connection from", clientsock.getpeername()
    while 1:
        data = clientsock.recv(4096)
        if not len(data):
            break
        clientsock.sendall(data)

    # Close the connection
    clientsock.close()

# Set up the socket.
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
s.bind((host, port))
s.listen(1)

while 1:
    try:
        clientsock, clientaddr = s.accept()
    except KeyboardInterrupt:
        raise
    except:
        traceback.print_exc()
        continue

    t = Thread(target = handlechild, args = [clientsock])
    t.setDaemon(1)
    t.start()
