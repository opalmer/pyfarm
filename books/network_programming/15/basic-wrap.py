#!/usr/bin/env python
# Basic SSL example with wrapper - Chapter 15 - basic-wrap.py

import socket, sys

class sslwrapper:
    def __init__(self, sslsock):
        self.sslsock = sslsock
        self.readbuf = ''
        self.eof = 0

    def write(self, buf):
        byteswritten = 0
        while byteswritten < len(buf):
            byteswritten += self.sslsock.write(buf[byteswritten:])

    def _read(self, n):
        retval = ''
        while not self.eof:
            try:
                retval = self.sslsock.read(n)
            except socket.sslerror, err:
                if (err[0]) in [socket.SSL_ERROR_ZERO_RETURN,
                                socket.SSL_ERROR_EOF]:
                    self.eof = 1
                elif (err[0]) in [socket.SSL_ERROR_WANT_READ,
                                  socket.SSL_ERROR_WANT_WRITE]:
                    continue
                else:
                    raise
            break
  
        if len(retval) == 0:
            self.eof = 1
        return retval

    def read(self, n):
        if len(self.readbuf):
            # Return the stuff in readbuf, even if less than n.
            # It might contain the rest of the line, and if we try to
            # read more, might block waiting for data that is not
            # coming to arrive.
            bytesfrombuf = min(n, len(self.readbuf))
            retval = self.readbuf[:bytesfrombuf]
            self.readbuf = self.readbuf[bytesfrombuf:]
            return retval
        retval = self._read(n)
        if len(retval) > n:
            self.readbuf = retval[n:]
            return retval[:n]
        return retval

    def readline(self, newlinestring = "\n"):
        retval = ''
        while 1:
            linebuf = self.read(1024)
            if not len(linebuf):
                return retval
            nlindex = linebuf.find(newlinestring)
            if nlindex != -1:
                retval += linebuf[:nlindex + len(newlinestring)]
                self.readbuf = linebuf[nlindex + len(newlinestring):] \
                        + self.readbuf
                return retval
            else:
                retval += linebuf

print "Creating socket...",
s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
print "done."

print "Connecting to remote host...",
s.connect(("www.openssl.org", 443))
print "done."

print "Establishing SSL...",
ssl = socket.ssl(s)
print "done."

ssl = sslwrapper(ssl)

print "Requesting document...",
ssl.write("HEAD / HTTP/1.0\r\n\r\n")
print "done."

s.shutdown(1)

while 1:
    line = ssl.readline("\r\n")
    if not len(line):
        break
    print "Received line:", line.strip()

s.close()

