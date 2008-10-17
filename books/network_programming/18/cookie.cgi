#!/usr/bin/env python
# CGI cookie example - Chapter 18 - cookie.cgi

import cgitb
cgitb.enable()

import cgi, os, urllib, Cookie

def getCookie():
    if os.environ.has_key('HTTP_COOKIE'):
        cookiestring = os.environ['HTTP_COOKIE']
    else:
        cookiestring = ''
    return Cookie.SimpleCookie(cookiestring)

def dispCookie():
    cookie = getCookie()
    print "Found the following cookies:<UL>"
    foundcookies = 0
    for key in cookie.keys():
        morsel = cookie[key]
        print "<LI>%s: %s" % (cgi.escape(key), cgi.escape(morsel.value))
        foundcookies += 1
    print "</UL><P>"
    if foundcookies:
        print '<A HREF="%s?action=delCookie">Click here</A>' % \
                os.environ['SCRIPT_NAME']
        print ' to delete the testcookie.<P>'

def setCookie(value, maxage):
    cookie = getCookie()
    cookie['testcookie'] = value
    cookie['testcookie']['max-age'] = maxage
    print cookie.output()
    
print "Content-type: text/html"
form = cgi.FieldStorage()
action = form.getfirst('action')
if action == 'setCookie':
    setCookie(form.getfirst('cookieval'), 60*60*24*365)
    print                               # Signal end of the headers
    print """<HTML><HEAD><TITLE>Cookie Set</TITLE></HEAD><BODY>
    The cookie has been set.  Click <A HREF="%s">here</A> to return to the 
    main page.</BODY></HTML>""" % os.environ['SCRIPT_NAME']
elif action == 'delCookie':
    setCookie('fake', 0)
    print                               # Signal end of the headers
    print """<HTML><HEAD><TITLE>Cookie deleted</TITLE></HEAD><BODY>
    The cookie has been deleted.  Click <A HREF="%s">here</A> to return to
    the main page.</BODY></HTML>""" % os.environ['SCRIPT_NAME']
else:
    print
    print """<HTML><HEAD><TITLE>CGI Cookie Example</TITLE></HEAD><BODY>"""
    dispCookie()
    print """<FORM METHOD="GET" ACTION="%s">""" % os.environ['SCRIPT_NAME']
    for value in ['Red', 'Green', 'Blue', 'White', 'Black']:
        print '<INPUT TYPE="radio" NAME="cookieval" VALUE="%s"> %s<BR>' % \
                (value, value)
    print """<INPUT TYPE="submit" NAME="action" VALUE="setCookie">
    </FORM>
    </BODY>
    </HTML>"""
