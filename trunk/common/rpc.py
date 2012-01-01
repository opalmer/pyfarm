# No shebang line, this module is meant to be imported
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

import os
import socket
import logging
import xmlrpclib

import preferences

from twisted.internet import reactor
from twisted.web import resource, xmlrpc
from twisted.python import log

TEST_MODE = False

class Service(xmlrpc.XMLRPC):
    '''
    Base twisted xmlrpc service, contains the stand methods
    and attributes to be inherited by all xmlrpc instances
    '''
    def __init__(self):
        resource.Resource.__init__(self)
        self.allowNone = True
        self.useDateTime = True
    # end __init__

    def _blockShutdown(self):
        '''
        used to block the shutdown process, this method
        should be overriden by the child class
        '''
        return False
    # end _blockShutdown

    def _blockRestart(self):
        '''
        used to block the restart process, this method
        should be overriden by the child class
        '''
        return False
    # end _blockRestart

    def _runShutdown(self):
        '''
        called when a shutdown is requested, this method
        should be overriden by the child class
        '''
        pass
    # end _runShutdown

    def _runRestart(self):
        '''
        called when a restart is requested, this method
        should be overriden by the child class
        '''
        pass
    # end _runRestart

    def xmlrpc_test_mode(self, value):
        '''
        used for testing this will prevent the client from actually restarting
        or shutting down
        '''
        global TEST_MODE
        TEST_MODE = value
        log.msg("test mode set to %s" % TEST_MODE)
    # end xmlrpc_test_mode

    def xmlrpc_ping(self):
        '''
        Simply return True.  This call should be used to query
        if a connection can be opened to the server.
        '''
        return True
    # end xmlrpc_ping

    def xmlrpc_shutdown(self, force=False):
        '''
        shutdown the client and reactor

        :param boolean force:
            if True run shutdown reguardless of the block state

        :exception xmlrpc.Fault(9):
            raised if the shutdown was blocked and not forced

        :exception xmlrpc.Fault(11):
            raised if the preferences have disabled the shutdown
        '''
        os.environ['PYFARM_RESTART'] = 'false'
        if not preferences.SHUTDOWN_ENABLED:
            raise xmlrpc.Fault(11, "shutdown disabled")

        block = self._blockShutdown()

        if block and not force:
            msg = "shutdown blocked, use force to override"
            raise xmlrpc.Fault(9, msg)

        elif block and force:
            log.msg("shutdown forced!", logLevel=logging.WARNING)

        if not TEST_MODE:
            self._runShutdown()
            reactor.callLater(1.0, reactor.stop)

        return True
    # end xmlrpc_shutdown

    def xmlrpc_restart(self, force=False):
        '''
        restart the client

        :exception xmlrpc.Fault(10):
            raised if restart is disabled via the preferences
        '''
        if not preferences.RESTART_ENABLED:
            raise xmlrpc.Fault(10, "restart disabled")

        if self.xmlrpc_shutdown(force) and not TEST_MODE:
            os.environ['PYFARM_RESTART'] = 'true'
    # end xmlrpc_restart
# end Service

def ping(host):
    '''
    returns True if we can connect and ping
    the remote host
    '''
    rpc = xmlrpclib.ServerProxy("http://%s" % host, allow_none=True)

    try:
        rpc.ping()
        log.msg('successfully pinged %s' % host)

    except socket.gaierror:
        log.msg('failed to ping %s' % host)
        return False
# end ping