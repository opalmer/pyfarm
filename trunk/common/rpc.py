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

from __future__ import with_statement

import os
import re
import types
import logging
import socket
import xmlrpclib

import preferences

from twisted.internet import reactor
from twisted.web import resource, xmlrpc
from twisted.python import log

TEST_MODE = False
RE_ADDR = re.compile(r'''http://(.+):(\d+)''')

class Service(xmlrpc.XMLRPC):
    '''
    Base twisted xmlrpc service, contains the stand methods
    and attributes to be inherited by all xmlrpc instances
    '''
    def __init__(self, log_stream):
        resource.Resource.__init__(self)
        self.allowNone = True
        self.useDateTime = True
        self.log_stream = log_stream

        # do not setup the log stream if what was passed
        # in was not actually a file stream
        if not isinstance(log_stream, types.FileType):
            self.log_stream = None
            log.msg(
                "service log stream established, some method will not function",
                logLevel=logging.WARNING
            )
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

    def xmlrpc_service_log(self, split=True):
        '''returns the current contents of the service log'''
        if not self.log_stream:
            return None

        with open(self.log_stream.name, 'r') as log:
            data = log.read()

        if split:
            return data.split("\n")

        return data
    # end xmlrpc_service_log
# end Service


class Connection(object):
    '''
    Generic rpc object which implements the Twisted xmlrpc
    proxy object.  The constructor for this class will
    either except a hostname and port or a hostname with
    the port included.

    >>> a = Connection('hostname', 9030)
    >>> b = Connection('hostname:9030')

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
        # split the hostname into port and hostname if
        # port is None and ":" in hostname
        if isinstance(port, types.NoneType) and ':' in hostname:
            hostname, port = RE_ADDR.match(hostname).groups()

        # be sure everything was setup properly
        if not hostname or not port:
            raise RuntimeError("hostname or port not passed to rpc.Connection")

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
        log.msg("rpc call to %s failed, iterating over failure below" % self.url)

        for error in args:
            if isinstance(error, types.InstanceType):
                if error.type == xmlrpclib.Fault:
                    error.printTraceback()
            else:
                print str(error)
    # end __fail

    def call(self, method, args=None, success=None, failure=None):
        args = args or []
        success = success or self.__success
        failure = failure or self.__failure or self.__fail
        remote = self.proxy.callRemote(method, args)

        if success:
            remote.addCallback(success)

        if failure:
            remote.addErrback(failure)
    # end call
# end Connection


def ping(hostname, port):
    '''return True if we can connect to the remote xmlrpc service'''
    try:
        rpc = xmlrpclib.ServerProxy(
            "http://%s:%i" % (hostname, port), allow_none=True
        )
        rpc.ping()
        log.msg("successfully received ping from %s" % hostname)
        return True

    except socket.error:
        log.msg("failed to ping %s" % hostname, logLevel=logging.WARNING)
        return False
# end ping
