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
import types
import atexit
import signal
import threading
import SocketServer
from SimpleXMLRPCServer import SimpleXMLRPCServer, SimpleXMLRPCRequestHandler

PID = os.getpid()
LOCK = threading.Lock()
NAMED_LOCKS = {}
PORT = 8080
HOSTNAME = '' # TODO: get the real hostname
ADDRESS = '' # TODO: get the real address


class resourcelock(object):
    def __init__(self, name=None):
        if type(name) not in types.StringTypes:
            self.func = name
            self.lock = LOCK
        else:
            if name not in NAMED_LOCKS:
                NAMED_LOCKS[name] = threading.Lock()

            self.lock = NAMED_LOCKS[name]
            self.func = None
    # END __init__

    def __call__(self, *args, **kwargs):
        func = self.func or args[0]
        def wrapped(*args, **kwargs):
            try:
                self.lock.acquire()
                return func(*args, **kwargs)

            finally:
                self.lock.release()

        if self.func:
            return wrapped(*args, **kwargs)
        return wrapped
    # END __call__
# END resourcelock

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
    @resourcelock("addJob")
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
    # TODO: Update to use real address/hostname/etc
    print "====== Starting Job Server ======"
    print "Process: %i" % PID
    print "Address: xxx.xxx.xxx.xxx"
    print "Hostname: <hostname>"
    print "Port: %i" % PORT
    print "================================="

    atexit.register(exitFunction)
    server = AsyncXMLRPCServer((HOSTNAME, PORT), SimpleXMLRPCRequestHandler)
    server.register_instance(JobDatabase())
    server.serve_forever()