# mod_python test example - Chapter 19 - test.py

from mod_python import apache
from sys import version

def writeinfo(req, name, value):
    req.write("<DT>%s</DT><DD>%s</DD>\n" % (name, value))

def handler(req):
    req.content_type = "text/html"
    if req.header_only:
        # Don't supply the body
        return apache.OK
    
    req.write("""<HTML><HEAD><TITLE>mod_python is working</TITLE>
    </HEAD>
    <BODY>
    <H1>mod_python is working</H1>
    You have successfully configured mod_python on your Apache system.
    Here is some information about the environment and this request:
    <P>
    <DL>
    """)

    writeinfo(req, "Client IP", req.get_remote_host(apache.REMOTE_NOLOOKUP))
    writeinfo(req, "URI", req.uri)
    writeinfo(req, "Filename", req.filename)
    writeinfo(req, "Canonical filename", req.canonical_filename)
    writeinfo(req, "Path_info", req.path_info)
    writeinfo(req, "Python version", version)

    req.write("</DL></BODY></HTML>\n")
    
    return apache.OK
    
