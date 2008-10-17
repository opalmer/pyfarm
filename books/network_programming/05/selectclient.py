#!/usr/bin/env python
# Non-blocking I/O with select() - Chapter 5 - selectclient.py

import socket, sys, select
port = 51423
host = 'localhost'

spinsize = 10
spinpos = 0
spindir = 1

def spin():
    global spinsize, spinpos, spindir
    spinstr = '.' * spinpos + \
              '|' + '.' * (spinsize - spinpos - 1)
    sys.stdout.write('\r' + spinstr + '  ')
    sys.stdout.flush()

    spinpos += spindir
    if spinpos < 0:
        spindir = 1
        spinpos = 1
    elif spinpos >= spinsize:
        spinpos -= 2
        spindir = -1

s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.connect((host, port))

while 1:
    infds, outfds, errfds = select.select([s], [], [s], 0.05)
    if len(infds):
        # Normally, one would use something like "for fd in infds" here.
        # We don't bother since there will only ever be a single file
        # descriptor there.
        data = s.recv(4096)
        if not len(data):
            print("\rRemote end closed connection; exiting.")
            break
        # Only one item in here -- if there's anything, it's for us.
        sys.stdout.write("\rReceived: " + data)
        sys.stdout.flush()
    if len(errfds):
        print "\rProblem occured; exiting."
        sys.exit(0)
    spin()
