#!/usr/bin/env python
# Zombie problem solution - Chapter 20 - zombiesol.py

import os, time, signal

def chldhandler(signum, stackframe):
    """Signal handler.  Runs on the parent and is called whenever
    a child terminates."""
    while 1:
        # Repeat as long as there are children to collect.
        try:
            result = os.waitpid(-1, os.WNOHANG)
        except:
            break
        print "Reaped child process %d" % result[0]
    # Re-set the signal handler so future signals trigger this function
    signal.signal(signal.SIGCHLD, chldhandler)

# Install signal handler so that chldhandler() gets called whenever
# child process terminates.
signal.signal(signal.SIGCHLD, chldhandler)

print "Before the fork, my PID is", os.getpid()

pid = os.fork()
if pid:
    print "Hello from the parent.  The child will be PID %d" % pid
    print "Sleeping 10 seconds..."
    time.sleep(10)
    print "Sleep done."
else:
    print "Child sleeping 5 seconds..."
    time.sleep(5)
