#!/usr/bin/env python
# Threading with locks - Chapter 21 - locks.py
import threading, time

# Initialize a simple variable
b = 50

# And a lock object
l = threading.Lock()

def threadcode():
    """This is run in the created threads"""
    global b
    print "Thread %s invoked" % threading.currentThread().getName()

    # Acquire the lock (will not return until a lock is acquired)
    l.acquire()
    try:
        print "Thread %s running" % threading.currentThread().getName()
        time.sleep(1)
        b = b + 50
        print "Thread %s set b to %d" % (threading.currentThread().getName(),
                                         b)
    finally:
        l.release()

print "Value of b at start of program:", b

childthreads = []

for i in range(1, 5):
    # Create new thread.
    t = threading.Thread(target = threadcode, name = "Thread-%d" % i)

    # This thread won't keep the program from terminating.
    t.setDaemon(1)

    # Start the new thread.
    t.start()
    childthreads.append(t)

for t in childthreads:
    # Wait for the child thread to exit.
    t.join()

print "New value of b:", b

