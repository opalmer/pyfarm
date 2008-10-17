#!/usr/bin/env python
# SMTP transmission with manual EHLO - Chapter 10 - ehlo.py

import sys, smtplib, socket

if len(sys.argv) < 4:
    print "Syntax: %s server fromaddr toaddr [toaddr...]" % sys.argv[0]
    sys.exit(255)

server = sys.argv[1]
fromaddr = sys.argv[2]
toaddrs = sys.argv[3:]

message = """To: %s
From: %s
Subject: Test Message from simple.py

Hello,

This is a test message sent to you from simple.py and smtplib.
""" % (', '.join(toaddrs), fromaddr)

try:
    s = smtplib.SMTP(server)
    code = s.ehlo()[0]
    usesesmtp = 1
    if not (200 <= code <= 299):
        usesesmtp = 0
        code = s.helo()[0]
        if not (200 <= code <= 299):
            raise SMTPHeloError(code, resp)

    if usesesmtp and s.has_extn('size'):
        print "Maximum message size is", s.esmtp_features['size']
        if len(message) > int(s.esmtp_features['size']):
            print "Message too large; aborting."
            sys.exit(2)
    s.sendmail(fromaddr, toaddrs, message)
except (socket.gaierror, socket.error, socket.herror, smtplib.SMTPException), \
        e:
    print " *** Your message may not have been sent!"
    print e
    sys.exit(1)
else:
    print "Message successfully sent to %d recipient(s)" % len(toaddrs)

