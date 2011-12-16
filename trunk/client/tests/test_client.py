#!/usr/bin/env python
#
# INITIAL: Dec 15 2011
# PURPOSE: Setup and execute unittests for the client
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

import types
import socket
import unittest
import xmlrpclib

from twisted.web import xmlrpc

class Base(unittest.TestCase):
    SERVER = "localhost"
    PORT = 9030
    SERVER_STRING = "http://%s:%i" % (SERVER, PORT)

    def setUp(self):
        self.rpc = xmlrpclib.ServerProxy(Base.SERVER_STRING, allow_none=True)
        self.rpc.test_mode(True)

        try:
            ping = lambda: self.rpc.ping()
            self.failUnlessEqual(ping(), True, "expected ping to return True")

        except socket.error, error:
            self.fail("no connection to client")
    # end setUp

    def tearDown(self):
        self.rpc.job.init()
        self.rpc.test_mode(False)
    # end tearDown

    def runWaitJob(self):
        job = self.rpc.job.run("python", "-c 'while True: pass'")
        print "====",job
        self.failUnless(
            isinstance(job, types.StringType),
            'wrong type from rpc.job.run'
        )
        self.failUnless(
            self.rpc.job.running(job),
            "%s should be running" % job
        )
    # end runWaitJob
# end Base

class Client(Base):
    def test_ping(self):
        self.failUnless(self.rpc.ping(), "ping should always return True")
    # end test_ping

    def test_online(self):
        self.failUnless(self.rpc.online(), "client reports it's not online")
        self.failIf(self.rpc.online(False), "client should now be offline")
        self.failIf(self.rpc.online(), "client reports it's online")
    # test_online

    def test_online_exception(self):
        self.failUnlessRaises(xmlrpc.Fault, self.rpc.online, -1)

        try:
            self.rpc.online(-1)

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 3,
                "expected fault code 3 not %i" % error.faultCode
            )
    # end test_online_exception

    def test_shutdown(self):
        self.runWaitJob()
        self.failUnlessRaises(xmlrpc.Fault, self.rpc.shutdown, False)

        try:
            self.rpc.shutdown()

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 9,
                "expected fault code 9 not %i" % error.faultCode
            )

        try:
            self.rpc.shutdown(True)

        except xmlrpc.Fault:
            self.fail("forced shutdown failed")
    # end test_shutdown

    def test_restart(self):
        self.runWaitJob()
        self.failUnlessRaises(xmlrpc.Fault, self.rpc.restart, False)

        try:
            self.rpc.restart()

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 9,
                "expected fault code 9 not %i" % error.faultCode
            )

        try:
            self.rpc.restart(True)

        except xmlrpc.Fault:
            self.fail("forced restart failed")
    # end test_restart

    def test_jobs_max(self):
        try:
            self.rpc.jobs_max(0)

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 8,
                "expected fault code 8 not %i" % error.faultCode
            )

        try:
            self.rpc.jobs_max(True)

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 3,
                "expected fault code 3 not %i" % error.faultCode
            )
    # end test_jobs_max

    def test_free(self):
        jobs_max = self.rpc.jobs_max()
#        print "=======",jobs_max
        for i in range(jobs_max+10):
            self.runWaitJob()
# end Client

if __name__ == '__main__':
    import sys
    sys.argv.append("-v")
    unittest.main()
