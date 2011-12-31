#!/usr/bin/env python
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import re
import socket

import preferences

from twisted.python import log
from twisted.internet import protocol, reactor, defer

HOSTNAME = socket.getfqdn(socket.gethostname())
RE_ISIP = re.compile(r'''\d{1,3}[.]\d{1,3}[.]\d{1,3}[.]\d{1,3}''')

def containsAddress(url):
    '''
    returns True if the given url contains an ip address
    otherwise returns False
    '''
    if ":" not in url:
        return False

    hostname, port = url.split(":")

    return bool(RE_ISIP.match(hostname))
# end isAddress

def getUrl(datagram):
    '''
    returns the the url enclosed in the datagram
    or None if it could not be found/properly
    processed
    '''
    if not isinstance(datagram, types.StringTypes):
        log.msg("incoming datagram does not contain the proper type")
        return

    # try out best to split the string
    # into two parts...failing that return None
    try:
        prefix, url = datagram.split("_")

    except ValueError:
        log.msg("failed to break '%s' into two parts" % datagram)
        return

    # Check to see if we can resolve the hostname.
    # This is more for debugging purposes since
    # we assume the network will be able to
    # resolve the hostname later on
    if not containsAddress(url):
        try:
            hostname, port = url.split(":")
            socket.gethostbyname(hostname)

        except socket.gaierror:
            log.msg("failed to resolve hostname '%s'" % hostname)

    return url
# end getUrl

class Server(protocol.DatagramProtocol):
    '''
    Base multicast server used to receieve and respond to a
    multicast request.  Once a datagram is receieved it is split
    into a prefix and suffix string which is then used to set
    the master host.
    '''
    def __init__(self):
        self.deferred = defer.Deferred()
    # end __init__

    def startProtocol(self):
        log.msg("joining multicast group %s" % preferences.MULTICAST_GROUP)
        self.transport.joinGroup(preferences.MULTICAST_GROUP)
    # end startProtocol

    def datagramReceived(self, datagram, address):
        url = None
        preifx = None

        # ensure the incoming datatypes matches what we expect
        if isinstance(datagram, types.StringTypes):
            prefix, url = datagram.split("_")

        else:
            raise RuntimeError("unexpected type in datagram")

        # if the prefix or url is not populated...then we have a problem
        if prefix == None or url == None:
            log.msg("failed to acquire prefix and/or url")

        # if the prefix is equal to the expected prefix
        # trigger the deferred callback and reply back with
        # our hostname and port
        elif prefix == preferences.MULTICAST_STRING:
            log.msg("incoming multicast '%s' from %s" % (url, str(address))
            self.deferred.callback(url)

            # create and send our reply back to the server
            args = (preferences.MULTICAST_STRING, HOSTNAME, prefereces.CLIENT_PORT)
            reply = "%s_%s:%i" % args
            log_args = (reply, str(address))
            log.msg("sending reply '%s' to multicast address %s" % log_args)
            self.transport.write(reply, address)

        elif prefix and url:
            log.msg("prefix and url are populated but contain unexpected values")
            log.msg("...data: (%s, %s)" % (str(prefix), str(url)))
    # end datagramReceived
# end Server


class Client(protocol.DatagramProtocol):
    '''
    protocol to receive a multicast reply after the initial
    packet has been sent.
    '''
    def __init__(self):
        self.deferred = defer.Deferred()
    # end __init__

    def datagramReceived(self, datagram, address):
        url = getUrl(datagram)
        log.msg("receieved multicast reply '%s' from %s" % (datagram, address))
        self.deferred.callback(url)
    # end datagramReceived
# end Client

def send(clients, hostname=None, port=None):
    '''
    sends a multicast signal to all hosts that
    are part of the mulitcast group

    :param string server:
        name of the server to send, defaults to the
        current hostnanme

    :param integer port:
        port the server is operating on, defaults to
        preferences.SERVER_PORT
    '''
    # prepare the data to send
    name = hostname or HOSTNAME
    port = port or preferences.SERVER_PORT
    data = "%s_%s:%i" % (preferences.MULTICAST_STRING, name, port)
    dest = (preferences.MULTICAST_GROUP, preferences.MULTICAST_PORT)

    # send the data unless we hit an error
    client = Client()
    client.deferred.addCallback(clients.add)
    multicast = reactor.listenUDP(0, client)
    log.msg("sending multicast '%s' to %s" % (data, str(dest)))
    try:
        multicast.write(data, dest)

    except socket.error, error:
        log.msg("error sending multicast: %s" % error)
# end send
