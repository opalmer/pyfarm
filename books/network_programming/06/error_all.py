#!/usr/bin/env python
# Obtain Web Page With Full Error Handling - Chapter 6
# error_all.py
import sys, urllib2, socket

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

bytesread = 0

while 1:
    try:
        data = fd.read(1024)
    except socket.error, e:
        print "Error reading data:", e
        sys.exit(3)

    if not len(data):
        break
    bytesread += len(data)
    sys.stdout.write(data)

if fd.info().has_key('Content-Length') and \
   long(fd.info()['Content-Length']) != long(bytesread):
    print "Expected a document of size %d, but read %d bytes" % \
          (long(fd.info()['Content-Length']), bytesread)
    sys.exit(4)
