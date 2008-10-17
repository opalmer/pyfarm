#!/usr/bin/env python
# Obtain Web Page Information With Error Document Handling - Chapter 6
# error_doc.py
import sys, urllib2

req = urllib2.Request(sys.argv[1])

try:
    fd = urllib2.urlopen(req)
except urllib2.HTTPError, e:
    print "Error retrieving data:", e
    print "Server errror document follows:\n"
    print e.read()
    sys.exit(1)
except urllib2.URLError, e:
    print "Error retrieving data:", e
    sys.exit(2)

print "Retrieved", fd.geturl()
info = fd.info()
for key, value in info.items():
    print "%s = %s" % (key, value)
    
