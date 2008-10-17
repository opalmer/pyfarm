#!/usr/bin/env python
# MIME message generation with 8-bit headers - Chapter 9
# mime_headers.py
# This program requires Python 2.2.2 or above

from email.MIMEText import MIMEText
from email.Header import Header
from email import Utils
message = """Hello,

This is a test message from Chapter 9.  I hope you enjoy it!

-- Anonymous"""

msg = MIMEText(message)
msg['To'] = 'recipient@example.com'
fromhdr = Header("Michael M\xfcller", 'iso-8859-1')
fromhdr.append('<mmueller@example.com>', 'ascii')
msg['From'] = fromhdr
msg['Subject'] = Header('Test Message, Chapter 9')
msg['Date'] = Utils.formatdate(localtime = 1)
msg['Message-ID'] = Utils.make_msgid()

print msg.as_string()
