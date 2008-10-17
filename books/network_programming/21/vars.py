#!/usr/bin/env python
# Threading with variables - Chapter 21 - vars.py
import threading, time

a = 50
b = 50
c = 50
d = 50

def printvars():
    print "a =", a
    print "b =", b
    print "c =", c
    print "d =", d

def threadcode():
    global a, b, c, d
    a += 50
    b = b + 50
    c = 100
    d = "Hello"
    print "[ChildThread] Values of variables in child thread:"
    printvars()

print "[MainThread] Values of variables before child thread:"
printvars()

# Create new thread.
t = threading.Thread(target = threadcode, name = "ChildThread")

# This thread won't keep the program from terminating.
t.setDaemon(1)

# Start the new thread.
t.start()

# Wait for the child thread to exit.
t.join()

print "[MainThread] Values of variables after child thread:"
printvars()

