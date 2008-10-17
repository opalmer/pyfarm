#!/usr/bin/env python
# Error Handling Example With Shutdown and File-Like Objects - Chapter 2

import socket, sys, time

host = sys.argv[1]
textport = sys.argv[2]
filename = sys.argv[3]

try:
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
except socket.error, e:
    print "Strange error creating socket: %s" % e
    sys.exit(1)

# Try parsing it as a numeric port number.

try:
    port = int(textport)
except ValueError:
    # That didn't work.  Look it up instread.
    try:
        port = socket.getservbyname(textport, 'tcp')
    except socket.error, e:
        print "Couldn't find your port: %s" % e
        sys.exit(1)

try:
    s.connect((host, port))
except socket.gaierror, e:
    print "Address-related error connecting to server: %s" % e
    sys.exit(1)
except socket.error, e:
    print "Connection error: %s" % e
    sys.exit(1)

fd = s.makefile('rw', 0)

print "sleeping..."
time.sleep(10)
print "Continuing."

try:
    fd.write("GET %s HTTP/1.0\r\n\r\n" % filename)
except socket.error, e:
    print "Error sending data: %s" % e
    sys.exit(1)

try:
    fd.flush()
except socket.error, e:
    print "Error sending data (detected by flush): %s" % e
    sys.exit(1)

try:
    s.shutdown(1)
except socket.error, e:
    print "Error sending data (detected by shutdown): %s" % e
    sys.exit(1)

while 1:
    try:
        buf = fd.read(2048)
    except socket.error, e:
        print "Error receiving data: %s" % e
        sys.exit(1)
    if not len(buf):
        break
    sys.stdout.write(buf)
    
