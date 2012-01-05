#!/usr/bin/env python


from common import loghandler, multicast, preferences

from twisted.internet import reactor

def setMaster(url):
    print "===========",url

multicast = multicast.Server()
multicast.deferred.addCallback(setMaster)
reactor.listenMulticast(preferences.MULTICAST_PORT, multicast)
reactor.run()
