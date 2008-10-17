#!/usr/bin/env python
# Asynchronous Echo Server - Chapter 22 - echoserver.py
# Compare to echo server in Chapter 3

import socket, traceback, os, sys, select

class stateclass:
    stdmask = select.POLLERR | select.POLLHUP | select.POLLNVAL

    def __init__(self, mastersock):
        """Initialize the state class"""
        self.p = select.poll()
        self.mastersock = mastersock
        self.watchread(mastersock)
        self.buffers = {}
        self.sockets = {mastersock.fileno(): mastersock}

    def fd2socket(self, fd):
        """Return a socket, given a file descriptor"""
        return self.sockets[fd]

    def watchread(self, fd):
        """Note interest in reading"""
        self.p.register(fd, select.POLLIN | self.stdmask)

    def watchwrite(self, fd):
        """Note interest in writing"""
        self.p.register(fd, select.POLLOUT | self.stdmask)

    def watchboth(self,fd):
        """Note interest in reading and writing"""
        self.p.register(fd, select.POLLIN | select.POLLOUT | self.stdmask)

    def dontwatch(self, fd):
        """Don't watch anything about this fd"""
        self.p.unregister(fd)

    def newconn(self, sock):
        """Process a new connection"""
        fd = sock.fileno()

        # Start out watching both since there will be an outgoing message
        self.watchboth(fd)

        # Put a greeting message into the buffer
        self.buffers[fd] = "Welcome to the echoserver, %s\n" % \
                str(sock.getpeername())
        self.sockets[fd] = sock

    def readevent(self, fd):
        """Called when data is ready to read"""
        try:
            # Read the data and append it to the write buffer.
            self.buffers[fd] += self.fd2socket(fd).recv(4096)
        except:
            self.closeout(fd)

        self.watchboth(fd)

    def writeevent(self, fd):
        """Called when data is ready to write."""
        if not len(self.buffers[fd]):
            # No data to send?  Take it out of the write list and return.
            self.watchread(fd)
            return
        
        try:
            byteswritten = self.fd2socket(fd).send(self.buffers[fd])
        except:
            self.closeout(fd)

        # Delete the text sent from the buffer
        self.buffers[fd] = self.buffers[fd][byteswritten:]

        # If the buffer is empty, we don't care about writing in the future.
        if not len(self.buffers[fd]):
            self.watchread(fd)

    def errorevent(self, fd):
        """Called when an error occurs"""
        self.closeout(fd)

    def closeout(self, fd):
        """Closes out a connection and removes it from data structures"""
        self.dontwatch(fd)
        try:
            self.fd2socket(fd).close()
        except:
            pass

        del self.buffers[fd]
        del self.sockets[fd]

    def loop(self):
        """Main loop for the program"""
        while 1:
            result = self.p.poll()
            for fd, event in result:
                if fd == self.mastersock.fileno() and event == select.POLLIN:
                    # Mastersock events mean a new client connection.
                    # Accept it, configure it, and pass it over to newconn()
                    try:
                        newsock, addr = self.fd2socket(fd).accept()
                        newsock.setblocking(0)
                        print "Got connection from", newsock.getpeername()
                        self.newconn(newsock)
                    except:
                        pass
                elif event == select.POLLIN:
                    self.readevent(fd)
                elif event == select.POLLOUT:
                    self.writeevent(fd)
                else:
                    self.errorevent(fd)

host = ''                               # Bind to all interfaces
port = 51423

s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
s.bind((host, port))
s.listen(1)
s.setblocking(0)

state = stateclass(s)
state.loop()
