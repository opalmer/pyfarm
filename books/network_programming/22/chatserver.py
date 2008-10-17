#!/usr/bin/env python
# Asynchronous Chat Server - Chapter 22 - chatserver.py

import socket, traceback, os, sys, select

class stateclass:
    stdmask = select.POLLERR | select.POLLHUP | select.POLLNVAL

    def __init__(self, mastersock):
        self.p = select.poll()
        self.mastersock = mastersock
        self.watchread(mastersock)
        self.readbuffers = {}
        self.writebuffers = {}
        self.sockets = {mastersock.fileno(): mastersock}

    def fd2socket(self, fd):
        return self.sockets[fd]

    def watchread(self, fd):
        self.p.register(fd, select.POLLIN | self.stdmask)

    def watchwrite(self, fd):
        self.p.register(fd, select.POLLOUT | self.stdmask)

    def watchboth(self,fd):
        self.p.register(fd, select.POLLIN | select.POLLOUT | self.stdmask)

    def dontwatch(self, fd):
        self.p.unregister(fd)

    def sendtoall(self, text, originfd):
        for line in text.split("\n"):
            line = line.strip()
            transmittext = str(self.fd2socket(originfd).getpeername()) + \
                    ": " + line + "\n"
            for fd in self.writebuffers.keys():
                self.writebuffers[fd] += transmittext
                self.watchboth(fd)

    def newconn(self, sock):
        fd = sock.fileno()
        self.watchboth(fd)
        self.writebuffers[fd] = "Welcome to the chat server, %s\n" % \
                str(sock.getpeername())
        self.readbuffers[fd] = ""
        self.sockets[fd] = sock

    def readevent(self, fd):
        try:
            # Read the data and append it to the write buffer.
            self.readbuffers[fd] += self.fd2socket(fd).recv(4096)
        except:
            self.closeout(fd)

        parts = self.readbuffers[fd].split("SEND")
        if len(parts) < 2:
            # No SEND command received
            return
        elif parts[-1] == '':
            # Nothing follows the SEND command; send what we have and
            # ignore the rest.
            self.readbuffers[fd] = ""
            sendlist = parts[:-1]
        else:
            # The last element has data for which a SEND has not yet been
            # seen; push it onto the buffer and process the rest.
            self.readbuffers[fd] = parts[-1]
            sendlist = parts[:-1]

        for item in sendlist:
            self.sendtoall(item.strip(), fd)

    def writeevent(self, fd):
        if not len(self.writebuffers[fd]):
            # No data to send?  Take it out of the write list and return.
            self.watchread(fd)
            return
        
        try:
            byteswritten = self.fd2socket(fd).send(self.writebuffers[fd])
        except:
            self.closeout(fd)

        self.writebuffers[fd] = self.writebuffers[fd][byteswritten:]

        if not len(self.writebuffers[fd]):
            self.watchread(fd)

    def errorevent(self, fd):
        self.closeout(fd)

    def closeout(self, fd):
        self.dontwatch(fd)
        try:
            self.fd2socket(fd).close()
        except:
            pass

        del self.writebuffers[fd]
        del self.sockets[fd]

    def loop(self):
        while 1:
            result = self.p.poll()
            for fd, event in result:
                if fd == self.mastersock.fileno() and event == select.POLLIN:
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
