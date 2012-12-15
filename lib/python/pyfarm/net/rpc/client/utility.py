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

import socket
import xmlrpclib
from twisted.web import xmlrpc
from twisted.internet import reactor

from pyfarm.logger import Logger

logger = Logger(__file__)

def xmlping(hostname, port, success=None, failure=None):
    '''
    Attempts to run the xmlrpc ping method on the host and port.
    When not running in a reactor this will attempt to call the
    respective callback (although they are not required.

    .. note::
        When running under the reactor this method will require
        at least a success method.  If a failure method is not provided
        then one will be constructed to log the failure

    '''
    ident = "%s:%i" % (hostname, port)
    url = "http://%s" % ident

    if not reactor.running:
        try:
            rpc = xmlrpclib.ServerProxy(url, allow_none=True)
            rpc.ping()
            logger.debug("successfully received ping from %s" % ident)

            if callable(success):
                success(hostname, port)

            return True

        except socket.error:
            logger.warning("failed to ping %s" % ident)

            if callable(failure):
                failure(hostname, port)

            return False

    else:
        if not callable(success):
            raise TypeError("reactor is running, success must be callable")

        if not callable(failure):
            def failure(value):
                logger.error("failed to ping %s" % ident)

        rpc = xmlrpc.Proxy(url)
        return rpc.callRemote('ping').addCallbacks(success, failure)
# end xmlping
