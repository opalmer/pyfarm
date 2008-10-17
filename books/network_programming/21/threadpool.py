#!/usr/bin/env python
# Thread Pool - Chapter 21 - threadpool.py

import socket, traceback, os, sys, time
from threading import *

host = ''                               # Bind to all interfaces
port = 51423
MAXTHREADS = 3
lockpool = Lock()
busylist = {}
waitinglist = {}
queue = []
sem = Semaphore(0)

def handleconnection(clientsock):
    """Handle an incoming client connection."""
    lockpool.acquire()
    print "Received new client connection."
    try:
        if len(waitinglist) == 0 and (activeCount() - 1) >= MAXTHREADS:
            # Too many connections.  Just close it and exit.
            clientsock.close()
            return
        if len(waitinglist) == 0:
            startthread()

        queue.append(clientsock)
        sem.release()
    finally:
        lockpool.release()

def startthread():
    # Called by handleconnection when a new thread is needed.
    # Note: lockpool is already acquired when this function is called.
    print "Starting new client processor thread"
    t = Thread(target = threadworker)
    t.setDaemon(1)
    t.start()

def threadworker():
    global waitinglist, lockpool, busylist
    time.sleep(1) # Simulate expensive startup
    name = currentThread().getName()
    try:
        lockpool.acquire()
        try:
            waitinglist[name] = 1
        finally:
            lockpool.release()
        
        processclients()
    finally:
        # Clean up if the thread is dying for some reason.
        # Can't lock here -- we may already hold the lock, but it's OK
        print "** WARNING** Thread %s died" % name
        if name in waitinglist:
            del waitinglist[name]
        if name in busylist:
            del busylist[name]

        # Start a replacement thread.
        startthread()

def processclients():
    global sem, queue, waitinglist, busylist, lockpool
    name = currentThread().getName()

    while 1:
        sem.acquire()
        lockpool.acquire()
        try:
            clientsock = queue.pop(0)
            del waitinglist[name]
            busylist[name] = 1
        finally:
            lockpool.release()

        try:
            print "[%s] Got connection from %s" % \
                    (name, clientsock.getpeername())
            clientsock.sendall("Greetings.  You are being serviced by %s.\n" %\
                    name)
            while 1:
                data = clientsock.recv(4096)
                if data.startswith('DIE'):
                    sys.exit(0)
                if not len(data):
                    break
                clientsock.sendall(data)
        except (KeyboardInterrupt, SystemExit):
            raise
        except:
            traceback.print_exc()

        # Close the connection

        try:
            clientsock.close()
        except KeyboardInterrupt:
            raise
        except:
            traceback.print_exc()

        lockpool.acquire()
        try:
           del busylist[name]
           waitinglist[name] = 1
        finally:
            lockpool.release()

    
def listener():
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    s.bind((host, port))
    s.listen(1)

    while 1:
        try:
            clientsock, clientaddr = s.accept()
        except KeyboardInterrupt:
            raise
        except:
            traceback.print_exc()
            continue

        handleconnection(clientsock)

listener()
