#!/usr/bin/env python
# CGI Environment - Chapter 18 - environ.cgi

import cgitb
cgitb.enable()

import cgi

print "Content-type: text/html"
print

print """<HTML>
<HEAD>
<TITLE>CGI Environment</TITLE>
</HEAD>
<BODY>"""
cgi.print_environ()
print "</BODY></HTML>"

