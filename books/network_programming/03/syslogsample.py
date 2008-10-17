#!/usr/bin/env python
# Syslog example - Chapter 3 - syslogsample.py

import syslog, sys, StringIO, traceback, os

def logexception(includetraceback = 0):
    exctype, exception, exctraceback = sys.exc_info()
    excclass = str(exception.__class__)
    message = str(exception)

    if not includetraceback:
        syslog.syslog(syslog.LOG_ERR, "%s: %s" % (excclass, message))
    else:
        excfd = StringIO.StringIO()
        traceback.print_exception(exctype, exception, exctraceback, None,
                                  excfd)
        for line in excfd.getvalue().split("\n"):
            syslog.syslog(syslog.LOG_ERR, line)

def initsyslog():
    syslog.openlog("%s[%d]" % (os.path.basename(sys.argv[0]), os.getpid()),
                   0, syslog.LOG_DAEMON)
    syslog.syslog("Started.")

initsyslog()

try:
    raise RuntimeError, "Exception 1"
except:
    logexception(0)

try:
    raise RuntimeError, "Exception 2"
except:
    logexception(1)
    
syslog.syslog("I'm terminating.")
