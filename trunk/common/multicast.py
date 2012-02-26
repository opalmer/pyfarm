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

import preferences

from twisted.python import log
from twisted.internet import protocol, defer

HOSTNAME = socket.getfqdn(socket.gethostname())

class MulticastServer(protocol.DatagramProtocol):
    '''General multicast server'''
    JOINED_GROUP = False

    def __init__(self, name):
        self.name = name
        self.deferred = defer.Deferred()
        self.callback = None
    # end __init__

    def startProtocol(self):
        '''
        starts the protocol and joins the multicast group if it has
        not done so already
        '''
        if not MulticastServer.JOINED_GROUP:
            self.transport.joinGroup(preferences.MULTICAST_GROUP)
            log.msg("joined multicast group %s" % preferences.MULTICAST_GROUP)
            MulticastServer.JOINED_GROUP = True

        else:
            group = preferences.MULTICAST_GROUP
            log.msg("already a member of the %s multicast group" % group)

        log.msg("started multicast server - %s" % self.name)
    # end startProtocol
# end MulticastServer


class HeartbeatServer(MulticastServer):
    '''
    Base multicast server used to receieve and respond to a
    multicast request.  Once a datagram is receieved it is split
    into a prefix and suffix string which is then used to set
    the master host.
    '''
    def __init__(self):
        MulticastServer.__init__(self, 'heartbeat')
        self.deferred = defer.Deferred()
        self.callback = None
    # end __init__

    def __data(self, data):
        '''returns data from the datagram with the correct types'''
        hostname = data[1]
        force = data[2]

        # convert force to a boolean value
        if force == "True":
            force = True
        else:
            force = False

        return hostname, force
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

    def datagramReceived(self, datagram, address):
        # ensure the incoming datatypes matches what we expect
        if not isinstance(datagram, types.StringTypes):
            raise TypeError("unexpected type in datagram")

        data = datagram.split("_")

        # skip multicast packats that do not match
        # the hearbeat preference
        if data[0] != preferences.MULTICAST_HEARTBEAT_STRING:
            return

        # ignore packages that are the wrong length
        if not len(data) == 3:
            return

        # get all arguments and proper types from the datagram, skip
        # any datagram that is not the correct typename
        hostname, force = self.__data(data)

        # reset the callback if it has already been called
        if self.deferred.called:
            self.resetCallback()

        self.deferred.callback((hostname, force))
# end HeartbeatServer
