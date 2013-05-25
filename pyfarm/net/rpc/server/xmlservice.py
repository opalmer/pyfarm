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

from __future__ import with_statement

import os

from twisted.internet import reactor
from twisted.web import resource, xmlrpc

from pyfarm.logger import Logger
from pyfarm.pref import prefs
from pyfarm.net.rpc.client import utility

TEST_MODE = False
logger = Logger(__name__)

class XMLRPCService(xmlrpc.XMLRPC, Logger):
    """
    Base twisted xmlrpc service, contains the stand methods
    and attributes to be inherited by all xmlrpc instances
    """
    def __init__(self, log_stream):
        resource.Resource.__init__(self)
        Logger.__init__(self, self)
        self.allowNone = True
        self.useDateTime = True
        self.log_stream = log_stream
    # end __init__

    def _blockShutdown(self):
        """
        used to block the shutdown process, this method
        should be overriden by the child class
        """
        return False
    # end _blockShutdown

    def _blockRestart(self):
        """
        used to block the restart process, this method
        should be overriden by the child class
        """
        return False
    # end _blockRestart

    def _runShutdown(self):
        """
        called when a shutdown is requested, this method
        should be overriden by the child class
        """
        pass
    # end _runShutdown

    def _runRestart(self):
        """
        called when a restart is requested, this method
        should be overriden by the child class
        """
        pass
    # end _runRestart

    def xmlrpc_test_mode(self, value):
        """
        used for testing this will prevent the host from actually restarting
        or shutting down
        """
        global TEST_MODE
        TEST_MODE = value
        self.debug("test mode set to %s" % TEST_MODE)
    # end xmlrpc_test_mode

    def xmlrpc_ping(self, hostname=None, port=None, success=None, failure=None):
        """
        by default this function will return True however a hostname
        and port could also be provided so we can ping remote hosts

        :param string hostname:
            the remote host to ping

        :param integer port:
            the remote port to ping the given hostname on
        """
        self.debug("incoming ping request")
        if hostname and port:
            return utility.ping(hostname, port, success, failure)

        return True
    # end xmlrpc_ping

    def xmlrpc_shutdown(self, force=False):
        """
        shutdown the host and reactor

        :param boolean force:
            if True run shutdown reguardless of the block state

        :exception xmlrpc.Fault(9):
            raised if the shutdown was blocked and not forced

        :exception xmlrpc.Fault(11):
            raised if the preferences have disabled the shutdown
        """
        os.environ['PYFARM_RESTART'] = 'false'
        if not prefs.get('network.rpc.shutdown'):
            raise xmlrpc.Fault(11, "shutdown disabled")

        block = self._blockShutdown()

        if block and not force:
            msg = "shutdown blocked, use force to override"
            raise xmlrpc.Fault(9, msg)

        elif block and force:
            self.warning("shutdown forced!")

        if not TEST_MODE:
            self._runShutdown()
            reactor.callLater(1.0, reactor.stop)

        return True
    # end xmlrpc_shutdown

    def xmlrpc_restart(self, force=False):
        """
        restart the host

        :exception xmlrpc.Fault(10):
            raised if restart is disabled via the preferences
        """
        if not prefs.get('network.rpc.restart'):
            raise xmlrpc.Fault(10, "restart disabled")

        if self.xmlrpc_shutdown(force) and not TEST_MODE:
            os.environ['PYFARM_RESTART'] = 'true'
    # end xmlrpc_restart

    def xmlrpc_service_log(self, split=True):
        """returns the current contents of the service log"""
        if not self.log_stream:
            return None

        with open(self.log_stream.name, 'r') as log:
            data = log.read()

        if split:
            return data.split("\n")

        return data
    # end xmlrpc_service_log
# end XMLRPCService

