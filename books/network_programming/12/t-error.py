#!/usr/bin/env python
# Error handling Twisted - Chapter 12 - t-error.py
# Note: This example assumes you have Twisted 1.1.0 or above installed.
#
# This program expects a host name and a user name as command-line
# arguments.

from twisted.internet import defer, reactor, protocol
from twisted.protocols.imap4 import IMAP4Client
import sys, getpass

class IMAPClient(IMAP4Client):
    """Our IMAP protocol class.  This class simply starts our IMAP logic when
    a connection is established."""
    def connectionMade(self):
        print "I have successfully connected to the server!"
        IMAPLogic(self)
        print "connectionMade returning."
        
class IMAPFactory(protocol.ClientFactory):
    """A Twisted factory class.  This class will take a username and password
    when an instance is created, saving them for later use."""
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
        self.logintries = 1

        d = self.login()
        d.addCallback(self.loggedin)
        d.addErrback(self.loginerror)
        d.addCallback(self.logout)
        d.addCallback(self.stopreactor)

        # This is a generic catch-all handler -- if any unhandled error
        # occurs, shut down the connection and terminate the reactor.
        d.addErrback(self.errorhappened)

        print "IMAPLogic.__init__ returning."

    def login(self):
        print "Logging in..."
        return self.proto.login(self.factory.username, self.factory.password)

    def loggedin(self, data):
        """Called after we are logged in.  The IMAP4Protocol login() function
        will pass an argument containing None, so the data parameter is there
        to receive it."""
        print "I'm logged in!"

    def logout(self, data = None):
        """Call this to log out.  Any arguments specified are ignored."""
        print "Logging out."
        d = self.proto.logout()
        return d

    def stopreactor(self, data = None):
        """Call this to stop the reactor."""
        print "Stopping reactor."
        reactor.stop()

    def errorhappened(self, failure):
        print "An error occurred:", failure.getErrorMessage()
        print "Because of the error, I am logging out and stopping reactor..."
        d = self.logout()
        d.addBoth(self.stopreactor)
        return failure

    def loginerror(self, failure):
        print "Your login failed (attempt %d)." % self.logintries
        if self.logintries >= 3:
            print "You have tried to log in three times; I'm giving up."
            return failure
        self.logintries += 1

        # Prompt for new login info.
        sys.stdout.write("New username: ")
        self.factory.username = sys.stdin.readline().strip()
        self.factory.password = getpass.getpass("New password: ")

        # And try it again.  Send errors back here.
        d = self.login()
        d.addErrback(self.loginerror)
        return d

password = getpass.getpass("Enter password for %s on %s: " % \
        (sys.argv[2], sys.argv[1]))
reactor.connectTCP(sys.argv[1], 143, IMAPFactory(sys.argv[2], password))
reactor.run()

