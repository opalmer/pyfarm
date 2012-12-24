# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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

from twisted.web import xmlrpc
from pyfarm.logger import Logger


class XMLRPCConnection(Logger):
    '''
    Generic rpc object which implements the Twisted xmlrpc
    proxy object.  The constructor for this class will
    either except a hostname and port or a hostname with
    the port included.

    >>> a = XMLRPCConnection('hostname', 9030)
    >>> b = XMLRPCConnection('hostname:9030')

    :param string hostname:
        the hostname or hostname and port to connect to

    :param integer port:
        the port to connect to

    :param callable success:
        method or function to call on a successful result

    :param callable failure
        method or function to call failed call

    :exception RuntimeError:
        raised if we somehow end up without a hostname
        and/port port
    '''
    def __init__(self, hostname,
                    port=None, success=None, failure=None):
        Logger.__init__(self.__class__.__name__)
        # split the hostname into port and hostname if
        # port is None and ":" in hostname
        if port is None and ':' in hostname:
            hostname, port = hostname.split(":")

        # be sure everything was setup properly
        if not hostname or not port:
            raise RuntimeError("hostname or port not passed to rpc.XMLRPCConnection")

        self.hostname = hostname
        self.port = int(port)
        self.__success = success
        self.__failure = failure

        # construct the proxy object
        self.url = "http://%s:%i" % (self.hostname, self.port)
        self.proxy = xmlrpc.Proxy(self.url, allowNone=True)
    # end __init__

    def __fail(self, *args):
        '''
        If no other deferred error handlers are defined, this will
        be the default
        '''
        self.debug("rpc call to %s failed, iterating over failure below" % self.url)

        for error in args:
            if hasattr(error, 'printTraceback') and callable(error.printTraceback):
                error.printTraceback()
            else:
                self.error(str(error))
    # end __fail

    def call(self, *args):
        success = self.__success
        failure = self.__failure or self.__fail
        remote = self.proxy.callRemote(args[0], *args[1:])

        if success:
            remote.addCallback(success)

        if failure:
            remote.addErrback(failure)
    # end call
# end XMLRPCConnection

