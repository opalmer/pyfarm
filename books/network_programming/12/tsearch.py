#!/usr/bin/env python
# IMAP searching - Chapter 12 - tsearch.py
# Note: This example assumes you have Twisted 1.1.0 or above installed.
# Command-line args: host name, user name

from twisted.internet import defer, reactor, protocol
from twisted.protocols.imap4 import IMAP4Client, Query, Not, Or, MessageSet
import sys, getpass, email

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
        d.addCallback(lambda x: self.proto.examine('INBOX'))
        d.addCallback(self.runquery)
        d.addCallback(self.printqueryresult)
        d.addCallback(self.logout)
        d.addCallback(self.stopreactor)

        d.addErrback(self.errorhappened)

    def runquery(self, data = None):
        # Find messages without "test" in the subject, and that have either
        # \Seen or \Answered flags.
        subjq = Not(Query(subject = "test"))
        flagq = Or(Query(seen = 1), Query(answered = 1))
        return self.proto.search(subjq, flagq)

    def printqueryresult(self, result):
        print "The following %d messages matched:" % len(result)
        m = MessageSet()
        for item in result:
            m.add(item)
        print str(m)

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

