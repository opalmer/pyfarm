#!/usr/bin/python
from lib.Network import *

server = Broadcast()
for i in server.send():
        print i

print "Done."
