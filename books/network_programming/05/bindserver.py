#!/usr/bin/env python
# Echo Server Bound to Specific Address - Chapter 5 - bindserver.py
import socket, traceback

host = '127.0.0.1'
port = 51423

s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
s.bind((host, port))
s.listen(1)

while 1:
    clientsock, clientaddr = s.accept()
    # Process the connection
    print "Got connection from", clientsock.getpeername()
    while 1:
        data = clientsock.recv(4096)
        if not len(data):
            break
        clientsock.sendall(data)
    # Close the connection

    clientsock.close()

