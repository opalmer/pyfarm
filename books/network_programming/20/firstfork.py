#!/usr/bin/env python
# First fork example - Chapter 20 - firstfork.py

import os, time

print "Before the fork, my PID is", os.getpid()

if os.fork():
    print "Hello from the parent.  My PID is", os.getpid()
else:
    print "Hello from the child.  My PID is", os.getpid()
       
time.sleep(1)
print "Hello from both of us."
