#!/usr/bin/env python
# Traditional Message Generation with Date and Message-ID -- Chapter 9
# trad_gen_newhdrs.py
# This program requires Python 2.2.2 or above

from email.MIMEText import MIMEText
from email import Utils
message = """Hello,

This is a test message from Chapter 9.  I hope you enjoy it!

-- Anonymous"""

msg = MIMEText(message)
msg['To'] = 'recipient@example.com'
msg['From'] = 'Test Sender <sender@example.com>'
msg['Subject'] = 'Test Message, Chapter 9'
msg['Date'] = Utils.formatdate(localtime = 1)
msg['Message-ID'] = Utils.make_msgid()

print msg.as_string()
