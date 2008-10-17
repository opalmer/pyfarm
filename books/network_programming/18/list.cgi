#!/usr/bin/env python
# CGI list example - Chapter 18 - list.cgi

import cgitb
cgitb.enable()

import cgi, os, urllib

print "Content-type: text/html"
print

print """<HTML>
<HEAD>
<TITLE>CGI List Example</TITLE></HEAD><BODY>"""

form = cgi.FieldStorage()
print "You selected: "
selections = form.getlist('data')
printable = [cgi.escape(x) for x in selections]
print ", ".join(printable)


print """<FORM METHOD="GET" ACTION="%s">
Select some things:<P>""" % os.environ['SCRIPT_NAME']
for item in ['Red', 'Green', 'Blue', 'Black', 'White', 'Purple',
    'Python', 'Perl', 'Java', 'Ruby', 'K&R', 'C++', 'OCaml', 'Haskell',
    'Prolog']:
    print '<INPUT TYPE="checkbox" NAME="data" VALUE="%s">' % cgi.escape(item)
    print ' %s<BR>' % cgi.escape(item)

print """<INPUT TYPE="submit" NAME="submit" VALUE="Submit">
</FORM>
</BODY></HTML>""" 

