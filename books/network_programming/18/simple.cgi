#!/usr/bin/env python
# Simple CGI Example - Chapter 18 - simple.cgi

import cgitb
cgitb.enable()

import time
print "Content-type: text/html"
print

print """<HTML>
<HEAD>
<TITLE>Sample CGI Script</TITLE>
</HEAD>
<BODY>
The present time is %s.
</BODY>
</HTML>""" % time.strftime("%I:%M:%S %p %Z")
print

