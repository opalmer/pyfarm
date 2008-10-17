#!/usr/bin/env python
# CGI file example - Chapter 18 - file.cgi

import cgitb
cgitb.enable()

import cgi, os, urllib, md5

print "Content-type: text/html"
print

print """<HTML>
<HEAD>
<TITLE>CGI File Example</TITLE></HEAD><BODY>"""

form = cgi.FieldStorage()
if form.has_key('file'):
    fileitem = form['file']
    if not fileitem.file:
        print "Error: not a file upload.<P>"
    else:
        print "Got file: %s<P>" % cgi.escape(fileitem.filename)
        m = md5.new()
        size = 0
        while 1:
            data = fileitem.file.read(4096)
            if not len(data):
                break
            size += len(data)
            m.update(data)
        print "Received file of %d bytes.  MD5sum is %s<P>" % \
                (size, m.hexdigest())
else:
    print "No file found.<P>"

print """<FORM METHOD="POST" ACTION="%s" enctype="multipart/form-data">
File: <INPUT TYPE="file" NAME="file">
""" % os.environ['SCRIPT_NAME']
print """<INPUT TYPE="submit" NAME="submit" VALUE="Submit">
</FORM>
</BODY></HTML>""" 

