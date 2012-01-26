# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
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

import types
import socket
import logging

import preferences

from twisted.python import log
from twisted.internet import protocol, reactor, defer

HOSTNAME = socket.getfqdn(socket.gethostname())

class DiscoveryServer(protocol.DatagramProtocol):
    '''
    Base multicast server used to receieve and respond to a
    multicast request.  Once a datagram is receieved it is split
    into a prefix and suffix string which is then used to set
    the master host.
    '''
    def __init__(self):
        self.deferred = defer.Deferred()
        self.callback = None
    # end __init__

    def __data(self, datagram):
        '''returns data from the datagram with the correct types'''
        typename, force, hostname, port = datagram.split("_")

        # convert force to a boolean value
        if force == "True":
            force = True
        else:
            force = False

        # convert port to an integer if possible
        if port.isdigit():
            port = int(port)
        else:
            log.msg(
                "failed to convert %s to an integer!",
                logLevel=logging.ERROR
            )

        return typename, force, hostname, port
    # end __data

    def resetCallback(self):
        '''resets the callback so it can be called again'''
        if self.callback is None:
            raise RuntimeError("cannot reset callback, self.callback is None")

        self.deferred = defer.Deferred()
        self.addCallback(self.callback)
    # end resetCallback

    def addCallback(self, callback=None):
        '''sets the internal callback and the callback on self.deferred'''
        if self.callback is None:
            self.callback = callback
            name = callback.func_name
            log.msg("set callback for multicast.Server to %s" % name)

        self.deferred.addCallback(callback)
    # end addCallback

    def startProtocol(self):
        log.msg("joining multicast group %s" % preferences.MULTICAST_GROUP)
        self.transport.joinGroup(preferences.MULTICAST_GROUP)
    # end startProtocol

    def datagramReceived(self, datagram, address):
        # ensure the incoming datatypes matches what we expect
        if not isinstance(datagram, types.StringTypes):
            raise TypeError("unexpected type in datagram")

        # ensure the data is the correct structure
        if not datagram.count("_") != 4:
            return

        # get all arguments and proper types from the datagram, skip
        # any datagram that is not the correct typename
        typename, force, hostname, port = self.__data(datagram)

        # ignore any multicasts that are not what we're looking for
        if typename != preferences.MULTICAST_DISCOVERY_STRING:
            warn = "ignoring multicast from %s:%i, " % (hostname, port)
            warn += "%s is not a valid typename" % typename
            log.msg(warn, logLevel=logging.WARNING)
            return

        log.msg("incoming discovery multicast from %s:%i" % (hostname, port))

        # reset the callback if it has already been called
        if self.deferred.called:
            self.resetCallback()

        self.deferred.callback((hostname, port, force))
# end Server


def sendDiscovery(hostname, port, force):
    '''
    sends a multicast signal to all hosts that
    are part of the mulitcast group

    :param string server:
        name of the server to send, defaults to the
        current hostname

    :param integer port:
        port the server is operating on, defaults to
        preferences.SERVER_PORT

    :param boolean force:
        determines if we should inform the clients to replace
        their master server address
    '''

    args = (
        preferences.MULTICAST_DISCOVERY_STRING,
        preferences.MULTICAST_GROUP, preferences.MULTICAST_PORT
    )
    log.msg("sending '%s' to group %s:%i" % args)

    # prepare the data to send
    address = (preferences.MULTICAST_GROUP, preferences.MULTICAST_PORT)
    data = "_".join([
        preferences.MULTICAST_DISCOVERY_STRING, str(force),
        hostname, str(port)
    ])

    # write data to the mulicast then close the connection
    try:
        udp = reactor.listenUDP(0, protocol.DatagramProtocol())
        udp.write(data, address)
        udp.stopListening()

    except socket.error, error:
        log.msg("error sending multicast: %s" % error)
# end sendDiscovery
