#!/usr/bin/env python
# Basic connection with Twisted - Chapter 12 - tconn.py
# Note: This example assumes you have Twisted 1.1.0 or above installed.

from twisted.internet import defer, reactor, protocol
from twisted.protocols.imap4 import IMAP4Client
import sys

class IMAPClient(IMAP4Client):
    def connectionMade(self):
        print "I have successfully connected to the server!"
        d = self.getCapabilities()
        d.addCallback(self.gotcapabilities)

    def gotcapabilities(self, caps):
        if caps == None:
            print "Server did not return a capability list."
        else:
            for key, value in caps.items():
                print "%s: %s" % (key, str(value))

        # This is the last thing, so stop the reactor.
        self.logout()
        reactor.stop()
        
class IMAPFactory(protocol.ClientFactory):
    protocol = IMAPClient

    def clientConnectionFailed(self, connector, reason):
        print "Client connection failed:", reason
        reactor.stop()

reactor.connectTCP(sys.argv[1], 143, IMAPFactory())
reactor.run()

