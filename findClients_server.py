#!/usr/bin/python

from lib.Network import *

server = MulticastServer()
a = server.run()
print a
