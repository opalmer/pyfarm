#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
ABOUT: Async. chart server, see page 471
INITIAL: Oct. 16 2008
'''

import os
import sys
import socket
import select
import traceback

class stateclass:
    stdmask = select.POLLERR | select.POLLHUP | select.POLLNVAL
    
    def __init__(self, mastersock):
        '''Init. the state class'''
        self.p = select.poll()
        self.mastersock = mastersock
        self.watchread(mastersock)
        self.buffers = {}
        self.sockets = {mastersock.fileno(): mastersock}
        
    def fd2socket(self, fd):
        '''Return a socket, given a file descriptor'''
        return self.sockets[fd]
        
    def watchread(self, fd):
        '''Note interest in reading'''
        self.p.register(fd, select.POLLIN | self.stdmask)
        
    def watchwrite(self, fd):
        '''Note interest in writing'''
        self.p.register(fd, select.POLLOUT | self.stdmask)
        
    def watchboth(self, fd):
        '''Note interest in reading and writing'''
        self.p.register(fd, select.POLLIN | select.POLLOUT | self.stdmask)
        
    def dontwatch(self, fd):
        '''Don't watch anything about this fd'''
        self.p.unregister(fd)
        
    def newconn(self, sock):
        '''Process a new connection'''
        fd = sock.fileno()
        
        # start out watching both since htere will be an outgoing message
        self.watchboth(fd)
        
        # put a greeting message into the buffer
        self.buffers[fd] = "Welcome to the echoserver, %s\n" % \
            str(sock.getpeername())
        self.sockets[fd] = sock
        
    def readevent(self, fd):
        '''Called when data is ready to read'''
        try:
            # Read the data and append it to the write buffer
            self.buffers[fd] += self.fd2socket(fd).recv(4096)
        except:
            self.closeout(fd)
            
        self.watchboth(fd)
        
    def writeevent(self, fd):
        '''Called when data is ready to write'''
        if not len(self.buffers[fd]):
            # no data to send? Take it out of the write list and return
            self.watchread(fd)
            return
            try:
                byteswritten = self.fd2socket(fd).send(self.buffers[fd])
            except:
                self.closeout(fd)
                
            # delete the text sent from the buffer
            self.buffers[fd] = self.buffers[fd][byteswritten:]
            
            # if the buffer is empty, we don't care bout writing in the future
            if not len(self.buffers[fd]):
                self.watchread(fd)