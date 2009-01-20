#!/usr/bin/python

from lib.Network import *

a = TCPClient("localhost", 2012)
a.send('0', '27', 'query', 'please render it now')
