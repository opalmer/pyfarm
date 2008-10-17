#!/usr/bin/env python
# Basic connection and authentication with Twisted - Chapter 12
# tlogin.py
# Note: This example assumes you have Twisted 1.1.0 or above installed.
#
# This program expects a host name and a user name as command-line
# arguments.
#
# Note: this program will hang if given a bad password.

from twisted.internet import defer, reactor, protocol
from twisted.protocols.imap4 import IMAP4Client
import sys, getpass

class IMAPClient(IMAP4Client):
    """Your IMAP protocol class.  This class simply starts your IMAP logic when
    a connection is established."""
    def connectionMade(self):
        print "I have successfully connected to the server!"

        # Create the IMAPLogic object.  It will add some of its own methods
        # as callbacks.
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
        # Save off passed-in data for later use.
        self.proto = proto
        self.factory = proto.factory

        # Attempt to log in, giving the stored username and password.
        d = self.proto.login(self.factory.username, self.factory.password)

        # When logged in, call self.loggedin() and pass its result to
        # self.stopreactor().
        d.addCallback(self.loggedin)
        d.addCallback(self.stopreactor)

        # Tell the user what happened.
        print "IMAPLogic.__init__ returning."

    def loggedin(self, data):
        """Called after we are logged in.  The IMAP4Protocol login() function
        will pass an argument containing None, so the data parameter is there
        to receive it."""
        print "I'm logged in!"
        return self.logout()

    def logout(self):
        """Call this to log out.  Any arguments specified are ignored."""
        print "Logging out."
        d = self.proto.logout()
        return d

    def stopreactor(self, data = None):
        """Call this to stop the reactor."""
        print "Stopping reactor."
        reactor.stop()

# Read the password from the terminal
password = getpass.getpass("Enter password for %s on %s: " % \
        (sys.argv[2], sys.argv[1]))

# Connect to the remote system
reactor.connectTCP(sys.argv[1], 143, IMAPFactory(sys.argv[2], password))

# And run the program.
reactor.run()

