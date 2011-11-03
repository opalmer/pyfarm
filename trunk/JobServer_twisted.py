#!/usr/bin/env python
#
# INITIAL: Oct 17 2011
# PURPOSE: To run a XML-RPC server containing job related infomation
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
import atexit
import socket
from twisted.web import resource, xmlrpc, server

PID = os.getpid()
PORT = 8080
HOSTNAME = socket.gethostname()
ADDRESS = socket.gethostbyname(HOSTNAME)

@atexit.register
def exitFunction():
    print "Stopping Server"
    print "...dumping data structure"
# END exitFunction

class ClientStatus(xmlrpc.XMLRPC):
    def xmlrpc_jobs(self, hostname):
        print 'returning a list of jobs for %s' % hostname
        return range(5)


class Clients(xmlrpc.XMLRPC):
    def __init__(self):
        resource.Resource.__init__(self)
        self.subHandlers = {"status": ClientStatus()}
        self.allowNone = True
        self.useDateTime = True

    def xmlrpc_add(self, name):
        print "Adding client: %s" % name
        return True


class MainServer(xmlrpc.XMLRPC):
    """An example object to be published."""
    def __init__(self):
        resource.Resource.__init__(self)
        self.subHandlers = {"clients": Clients()}
        self.allowNone = True
        self.useDateTime = True

    def xmlrpc_echo(self, x):
        """
        Return all passed args.
        """
        print "echo: %s" % x
        import time
        time.sleep(3)
        return x

    def xmlrpc_fault(self):
        """
        Raise a Fault indicating that the procedure should not be used.
        """
        raise xmlrpc.Fault(123, "The fault procedure is faulty.")


if __name__ == '__main__':
    print "====== Starting Job Server ======"
    print "Process: %i" % PID
    print "Address: %s" % ADDRESS
    print "Hostname: %s" % HOSTNAME
    print "Port: %i" % PORT
    print "================================="

    from twisted.internet import reactor

    rpcserver = MainServer()
    reactor.listenTCP(PORT, server.Site(rpcserver))
    reactor.run()