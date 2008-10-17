#!/usr/bin/env python
# Obtain Web Page - Chapter 6 - dump_page.py
import sys, urllib2

req = urllib2.Request(sys.argv[1])
fd = urllib2.urlopen(req)
while 1:
    data = fd.read(1024)
    if not len(data):
        break
    sys.stdout.write(data)
