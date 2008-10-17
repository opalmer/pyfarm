#!/usr/bin/env python
# IMAP downloader - Chapter 12 - tdownload.py
# Note: This example assumes you have Twisted 1.1.0 or above installed.
# Command-line args: host name, user name, destination file

from twisted.internet import defer, reactor, protocol
from twisted.protocols.imap4 import IMAP4Client
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
        d.addCallback(lambda x: self.proto.fetchUID('1:*'))
        d.addCallback(self.handleuids)
        d.addCallback(self.logout)
        d.addCallback(self.stopreactor)

        d.addErrback(self.errorhappened)

    def handleuids(self, uids):
        dlist = []
        destfd = open(sys.argv[3], "at")
        for data in uids.values():
            uid = data['UID']
            d = self.proto.fetchSpecific(uid, uid = 1, peek = 1)
            d.addCallback(self.gotmessage, destfd, uid)
            dlist.append(d)
        dl = defer.DeferredList(dlist)
        dl.addCallback(lambda x, fd: fd.close(), destfd)
        return dl

    def gotmessage(self, data, destfd, uid):
        print "Received message UID", uid
        for key, value in data.items():
            print "Writing message", key
            i = value[0].index('BODY') + 2
            msg = email.message_from_string(value[0][i])
            destfd.write(msg.as_string(unixfrom = 1))
            destfd.write("\n")
           
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

