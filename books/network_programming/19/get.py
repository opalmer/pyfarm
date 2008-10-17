# mod_python GET example -- Chapter 19 -- get.py

from mod_python import apache, util
import time

monthmap = {1: 'January', 2: 'February', 3: 'March', 4: 'April', 5: 'May',
    6: 'June', 7: 'July', 8: 'August', 9: 'September', 10: 'October',
    11: 'November', 12: 'December'}

daymap = {0: 'Monday', 1: 'Tuesday', 2: 'Wednesday', 3: 'Thursday',
    4: 'Friday', 5: 'Saturday', 6: 'Sunday'}

def month_quiz(req):
    req.write("What month is it?<P>\n")
    for code, name in monthmap.items():
        req.write('<A HREF="%s?month=%d">%s</A><BR>' % (req.uri,
                  code, name))

def day_quiz(req):
    month = time.localtime()[1]
    req.write("What day is it?<P>\n")
    for code, name in daymap.items():
        req.write('<A HREF="%s?month=%d&day=%d">%s</A><BR>' % \
                  (req.uri, month, code, name))

def check_month_answer(req, answer):
    month = time.localtime()[1]
    if int(answer) == month:
        req.write("Yes, this is <B>%s</B>.<P>\n" % monthmap[month])
        return 1
    else:
        req.write("Sorry, you're wrong.  Try again:<P>\n")
        month_quiz(req)
        return 0

def check_day_answer(req, answer):
    day = time.localtime()[6]
    if int(answer) == day:
        req.write("Yes, this is <B>%s</B>.\n" % daymap[day])
        return 1
    else:
        req.write("Sorry, you're wrong.  Try again:<P>\n")
        day_quiz(req)
        return 0

def handler(req):
    req.content_type = "text/html"
    if req.header_only:
        return apache.OK

    req.write("""<HTML>
<HEAD>
<TITLE>mod_python GET Example</TITLE></HEAD><BODY>""")

    form = util.FieldStorage(req)

    if form.getfirst('month') == None:
        month_quiz(req)
    elif form.getfirst('day') == None:
        ismonthright = check_month_answer(req, form.getfirst('month'))
        if ismonthright:
            day_quiz(req)
    else:
        ismonthright = check_month_answer(req, form.getfirst('month'))
        if ismonthright:
            check_day_answer(req, form.getfirst('day'))

    req.write("</BODY></HTML>\n")
    return apache.OK

