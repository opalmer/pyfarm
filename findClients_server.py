#!/usr/bin/python

from lib.FarmLog import *
from lib.Network import *

server = MulticastServer()
a = server.run()
print a
