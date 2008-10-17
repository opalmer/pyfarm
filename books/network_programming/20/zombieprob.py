#!/usr/bin/env python
# Zombie problem demonstration - Chapter 20 - zombieprob.py

import os, time

print "Before the fork, my PID is", os.getpid()

pid = os.fork()
if pid:
    print "Hello from the parent.  The child will be PID %d" % pid
    print "Sleeping 120 seconds..."
    time.sleep(120)
