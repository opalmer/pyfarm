#!/usr/bin/env python
# CGI POST example - Chapter 18 - post.cgi

import cgitb
cgitb.enable()

import cgi, time, os

monthmap = {1: 'January', 2: 'February', 3: 'March', 4: 'April', 5: 'May',
    6: 'June', 7: 'July', 8: 'August', 9: 'September', 10: 'October',
    11: 'November', 12: 'December'}

daymap = {0: 'Monday', 1: 'Tuesday', 2: 'Wednesday', 3: 'Thursday',
    4: 'Friday', 5: 'Saturday', 6: 'Sunday'}

def print_month_quiz():
    print "What month is it?<P>"
    print '<FORM METHOD="POST" ACTION="%s">' % os.environ['SCRIPT_NAME']

    for code, name in monthmap.items():
        print '<INPUT NAME="month" TYPE="radio" VALUE="%d"> %s<BR>' % \
                (code, name)

    print '<INPUT TYPE="submit" NAME="submit" VALUE="Next &gt;&gt;">'
    print "</FORM>"

def print_day_quiz():
    month = time.localtime()[1]
    print "What day is it?<P>"
    print '<FORM METHOD="POST" ACTION="%s">' % os.environ['SCRIPT_NAME']
    print '<INPUT TYPE="hidden" NAME="month" VALUE="%d">' % month

    for code, name in daymap.items():
        print '<INPUT NAME="day" TYPE="radio" VALUE="%d"> %s<BR>' % \
                (code, name)

    print '<INPUT TYPE="submit" NAME="submit" VALUE="Next &gt;&gt;">'
    print "</FORM>"

def check_month_answer(answer):
    month = time.localtime()[1]
    if int(answer) == month:
        print "Yes, this is <B>%s</B>.<P>" % monthmap[month]
        return 1
    else:
        print "Sorry, you're wrong.  Try again:<P>"
        print_month_quiz()
        return 0

def check_day_answer(answer):
    day = time.localtime()[6]
    if int(answer) == day:
        print "Yes, this is <B>%s</B>." % daymap[day]
        return 1
    else:
        print "Sorry, you're wrong.  Try again:<P>"
        print_day_quiz()
        return 0

print "Content-type: text/html"
print

print """<HTML>
<HEAD>
<TITLE>CGI POST Example</TITLE></HEAD><BODY>"""

form = cgi.FieldStorage()

if form.getfirst('month') == None:
    print_month_quiz()
elif form.getfirst('day') == None:
    ismonthright = check_month_answer(form.getfirst('month'))
    if ismonthright:
        print_day_quiz()
else:
    ismonthright = check_month_answer(form.getfirst('month'))
    if ismonthright:
        check_day_answer(form.getfirst('day'))

print "</BODY></HTML>"

