#!/usr/bin/env python
# First thread example - Chapter 21 - firstthread.py
import threading, time
from sys import stdout

def sleepandprint():
    time.sleep(1)
    print "Hello from both of us."

def threadcode():
    stdout.write("Hello from the new thread.  My name is %s\n" %
                threading.currentThread().getName())
    sleepandprint()

print "Before starting a new thread, my name is", \
        threading.currentThread().getName()

# Create new thread.
t = threading.Thread(target = threadcode, name = "ChildThread")

# This thread won't keep the program from terminating.
t.setDaemon(1)

# Start the new thread.
t.start()
stdout.write("Hello from the main thread.  My name is %s\n" %
        threading.currentThread().getName())
sleepandprint()

# Wait for the child thread to exit.
t.join()
