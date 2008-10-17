#!/usr/bin/env python
# IMAP downloader with message deletion - Chapter 12
# tdownload-and-delete.py
# Note: This example assumes you have Twisted 1.1.0 or above installed.
# Command-line args: host name, user name, destination file
#
# WARNING: THIS PROGRAM WILL DELETE ALL MAIL FROM THE INBOX!

from twisted.internet import defer, reactor, protocol
from twisted.protocols.imap4 import IMAP4Client, MessageSet
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
        d.addCallback(lambda x: self.proto.select('INBOX'))
        d.addCallback(lambda x: self.proto.fetchUID('1:*'))
        d.addCallback(self.handleuids)
        d.addCallback(self.deletemessages)
        d.addCallback(self.logout)
        d.addCallback(self.stopreactor)

        d.addErrback(self.errorhappened)

    def handleuids(self, uids):
        self.uidlist = MessageSet()
        dlist = []
        destfd = open(sys.argv[3], "at")
        for num, data in uids.items():
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
        self.uidlist.add(int(uid))

    def deletemessages(self, data = None):
        print "Deleting messages", str(self.uidlist)
        d = self.proto.addFlags(str(self.uidlist), ["\\Deleted"], uid = 1)
        d.addCallback(lambda x: self.proto.expunge())
        return d
           
    def logout(self, data = None):
        return self.proto.logout()

    def stopreactor(self, data = None):
        reactor.stop()

    def errorhappened(self, failure):
        print "An error occured:", failure.getErrorMessage()
        d = self.logout()
        d.addBoth(self.stopreactor)
        return failure

print "WARNING: this program will delete all mail from the INBOX!"
password = getpass.getpass("Enter password for %s on %s: " % \
        (sys.argv[2], sys.argv[1]))
reactor.connectTCP(sys.argv[1], 143, IMAPFactory(sys.argv[2], password))
reactor.run()

