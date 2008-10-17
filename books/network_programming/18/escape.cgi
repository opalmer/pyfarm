#!/usr/bin/env python
# CGI escape example - Chapter 18 - escape.cgi

import cgitb
cgitb.enable()

import cgi, os, urllib

print "Content-type: text/html"
print

print """<HTML>
<HEAD>
<TITLE>CGI Escape Example</TITLE></HEAD><BODY>"""

form = cgi.FieldStorage()

if form.getfirst('data') == None:
    print "No submitted data.<P>"
else:
    print "Submitted data:<P>"
    print '<A HREF="%s?data=%s"><TT>%s</TT></A><P>' % \
            (os.environ['SCRIPT_NAME'],
             urllib.quote_plus(form.getfirst('data')),
             cgi.escape(form.getfirst('data')))

print """<FORM METHOD="GET" ACTION="%s">
Supply some data: 
<INPUT TYPE="text" NAME="data" WIDTH="40">
<INPUT TYPE="submit" NAME="submit" VALUE="Submit">
</FORM>
</BODY></HTML>""" % os.environ['SCRIPT_NAME']

