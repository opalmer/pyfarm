#!/usr/bin/env python
# Obtain Web Page Information - Chapter 6 - dump_info.py
import sys, urllib2

req = urllib2.Request(sys.argv[1])
fd = urllib2.urlopen(req)
print "Retrieved", fd.geturl()
info = fd.info()
for key, value in info.items():
    print "%s = %s" % (key, value)
    
