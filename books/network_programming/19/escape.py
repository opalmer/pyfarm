# mod_ptyhon escape example -- Chapter 9 -- escape.py

from mod_python import apache, util
import urllib
from cgi import escape

def handler(req):
    req.content_type = "text/html"
    if req.header_only:
        # Don't supply the body
        return apache.OK

    req.write("""<HTML>
<HEAD>
<TITLE>mod_python Escape Example</TITLE></HEAD><BODY>""")

    form = util.FieldStorage(req)

    if form.getfirst('data') == None:
        req.write("No submitted data.<P>\n")
    else:
        req.write("Submitted data:<P>\n")
        req.write('<A HREF="%s?data=%s"><TT>%s</TT></A><P>' % \
                  (req.uri,
                   urllib.quote_plus(form.getfirst('data')),
                   escape(form.getfirst('data'))))

    req.write("""<FORM METHOD="GET" ACTION="%s">
    Supply some data: 
    <INPUT TYPE="text" NAME="data" WIDTH="40">
    <INPUT TYPE="submit" NAME="submit" VALUE="Submit">
    </FORM>
    </BODY></HTML>\n""" % req.uri)

    return apache.OK

