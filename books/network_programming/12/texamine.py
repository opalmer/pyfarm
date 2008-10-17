#!/usr/bin/env python
# IMAP folder examine information - Chapter 12
# Note: This example assumes you have Twisted 1.1.0 or above installed.
# Command-line args: host name, user name

from twisted.internet import defer, reactor, protocol
from twisted.protocols.imap4 import IMAP4Client
import sys, getpass

class IMAPClient(IMAP4Client):
    def connectionMade(self):
        IMAPLogic(self)
        
class IMAPFactory(protocol.ClientFactory):
    protocol = IMAPClient
    def __init__(self, username, password):
        self.username = username
        self.password = password

    def clientConnectionFailed(self, connector, reason):
        print "Client connection failed:", reason
        reactor.stop()

class IMAPLogic:
    """This class implements the main logic for the program."""
    def __init__(self, proto):
        self.proto = proto
        self.factory = proto.factory
        d = self.proto.login(self.factory.username, self.factory.password)

        # This lambda creates a function that takes one argument,
        # ignores it, and returns the value of self.proto.examine().
        # It's useful because we don't want to pass the result of
        # self.proto.login() to examine().
        d.addCallback(lambda x: self.proto.examine('INBOX'))
        d.addCallback(self.examineresult)
        d.addCallback(self.logout)
        d.addCallback(self.stopreactor)

        d.addErrback(self.errorhappened)

    def examineresult(self, data):
        for key, value in data.items():
            if isinstance(value, tuple):
                print "%s: %s" % (key, ", ".join(value))
            else:
                print "%s: %s" % (key, value)

    def logout(self, data = None):
        return self.proto.logout()

    def stopreactor(self, data = None):
        reactor.stop()

    def errorhappened(self, failure):
        print "An error occured:", failure.getErrorMessage()
        d = self.logout()
        d.addBoth(self.stopreactor)
        return failure

password = getpass.getpass("Enter password for %s on %s: " % \
        (sys.argv[2], sys.argv[1]))
reactor.connectTCP(sys.argv[1], 143, IMAPFactory(sys.argv[2], password))
reactor.run()

