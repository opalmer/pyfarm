# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

import socket
import xmlrpclib
from twisted.web import xmlrpc
from twisted.internet import reactor

from pyfarm.logger import Logger

logger = Logger(__file__)

def xmlping(hostname, port, success=None, failure=None):
    """
    Attempts to run the xmlrpc ping method on the host and port.
    When not running in a reactor this will attempt to call the
    respective callback (although they are not required.

    .. note::
        When running under the reactor this method will require
        at least a success method.  If a failure method is not provided
        then one will be constructed to log the failure

    """
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
