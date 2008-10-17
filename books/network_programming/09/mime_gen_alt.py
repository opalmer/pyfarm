#!/usr/bin/env python
# MIME alternative generation - Chapter 9 - mime_gen_alt.py
# This program requires Python 2.2.2 or above

from email.MIMEText import MIMEText
from email.MIMEMultipart import MIMEMultipart
from email import Utils, Encoders
import mimetypes, sys

def alternative(data, contenttype):
    maintype, subtype = contenttype.split('/')
    if maintype == 'text':
        retval = MIMEText(data, _subtype=subtype)
    else:
        retval = MIMEBase(maintype, subtype)
        retval.set_payload(data)
        Encoders.encode_base64(retval)
    return retval

messagetext = """Hello,

This is a *great* test message from Chapter 9.  I hope you enjoy it!

-- Anonymous"""
messagehtml = """Hello,<P>
This is a <B>great</B> test message from Chapter 9.  I hope you enjoy
it!<P>
-- <I>Anonymous</I>"""


msg = MIMEMultipart('alternative')
msg['To'] = 'recipient@example.com'
msg['From'] = 'Test Sender <sender@example.com>'
msg['Subject'] = 'Test Message, Chapter 9'
msg['Date'] = Utils.formatdate(localtime = 1)
msg['Message-ID'] = Utils.make_msgid()

msg.attach(alternative(messagetext, 'text/plain'))
msg.attach(alternative(messagehtml, 'text/html'))
print msg.as_string()
