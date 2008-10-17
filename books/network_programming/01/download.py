#!/usr/bin/env python
# Chapter 1 - Download Example - download.py

import urllib, sys

f = urllib.urlopen(sys.argv[1])
while 1:
    buf = f.read(2048)
    if not len(buf):
        break
    sys.stdout.write(buf)
