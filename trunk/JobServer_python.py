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
import sys
import time
import atexit
import signal
import socket
import threading
import SocketServer
from SimpleXMLRPCServer import SimpleXMLRPCServer, SimpleXMLRPCRequestHandler

import lib.decorators

PID = os.getpid()
LOCK = threading.Lock()
NAMED_LOCKS = {}
PORT = 8080
HOSTNAME = socket.gethostname()
ADDRESS = socket.gethostbyname(HOSTNAME)

@atexit.register
def exitFunction():
    print "Stopping Server"
    print "...dumping data structure"
# END exitFunction

class AsyncXMLRPCServer(SocketServer.ThreadingMixIn, SimpleXMLRPCServer):
    pass
# END AsyncXMLRPCServer

class JobDatabase(object):
    def exit(self):
        os.kill(PID, signal.SIGINT)

    def echo(self, echo):
        print echo
        return True

    # lock resource so multiple threads do not attempt to change
    # the same information
    @lib.decorators.lock("addJob")
    def addJob(self, newJob):
        print "entering addJob"
        print "...adding %s" % newJob
        time.sleep(3)
        print "...sleeping"
        return True

    def queryJob(self, job):
        print "running query for %s" % job
        return True



if __name__ == '__main__':
    print "====== Starting Job Server ======"
    print "Process: %i" % PID
    print "Address: %s" % ADDRESS
    print "Hostname: %s" % HOSTNAME
    print "Port: %i" % PORT
    print "================================="

    server = AsyncXMLRPCServer((HOSTNAME, PORT), SimpleXMLRPCRequestHandler)
    server.register_instance(JobDatabase())
    server.serve_forever()