#!/usr/bin/env python
# Zombie problem solution with polling - Chapter 20 - zombiepoll.py

import os, time

def reap():
    """Try to collect zombie processes, if any."""
    while 1:
        try:
            result = os.waitpid(-1, os.WNOHANG)
        except:
            break
        print "Reaped child process %d" % result[0]

print "Before the fork, my PID is", os.getpid()

pid = os.fork()
if pid:
    print "Hello from the parent.  The child will be PID %d" % pid
    print "Parent sleeping 60 seconds..."
    time.sleep(60)
    print "Parent sleep done."
    reap()
    print "Parent sleeping 60 seconds..."
    time.sleep(60)
    print "Parent sleep done."
else:
    print "Child sleeping 5 seconds..."
    time.sleep(5)
    print "Child terminating."
