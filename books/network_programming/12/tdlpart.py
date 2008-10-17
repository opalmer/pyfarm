#!/usr/bin/env python
# IMAP part downloader - Chapter 12 - tdlpart.py
# Note: This example assumes you have Twisted 1.1.0 or above installed.
# Command-line args: host name, user name

from twisted.internet import defer, reactor, protocol
from twisted.protocols.imap4 import IMAP4Client
import sys, getpass, email

class IMAPClient(IMAP4Client):
    def connectionMade(self):
        IMAPLogic(self)
        
class IMAPFactory(protocol.ClientFactory):
    protocol = IMAPClient
    def __init__(self, username, password, uid, part):
        self.username = username
        self.password = password
        self.uid = uid
        self.part = part

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
        d.addCallback(lambda x: self.proto.fetchSpecific(self.factory.uid,
            uid = 1, headerNumber = self.factory.part.split('.'),
            peek = 1))
        d.addCallback(self.displaypart)
        d.addCallback(self.logout)
        d.addCallback(self.stopreactor)

        d.addErrback(self.errorhappened)

    def displaypart(self, data):
        for key, value in data.items():
            i = value[0].index('BODY') + 2
            print value[0][i]

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
sys.stdout.write("Enter message UID: ")
uid = int(sys.stdin.readline().strip())
sys.stdout.write("Enter message part: ")
part = sys.stdin.readline().strip()
reactor.connectTCP(sys.argv[1], 143, IMAPFactory(sys.argv[2], password, uid,
    part))
reactor.run()

