#!/usr/bin/env python
#
# INITIAL: Dec 15 2011
# PURPOSE: Setup and execute unittests for the client
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

import uuid
import types
import socket
import unittest
import xmlrpclib

from twisted.web import xmlrpc

rpc = xmlrpclib.ServerProxy("http://localhost:9031", allow_none=True)

class Base(unittest.TestCase):
    def setUp(self):
        rpc.test_mode(True)

        try:
            ping = lambda: rpc.ping()
            self.failUnlessEqual(ping(), True, "expected ping to return True")

        except socket.error, error:
            self.fail("no connection to client")
    # end setUp

    def tearDown(self):
        rpc.job.init()
        rpc.test_mode(False)
    # end tearDown

    def runWaitJob(self):
        job = rpc.job.run("python", "-c 'while True: pass'")
        self.failUnless(
            isinstance(job, types.StringType),
            'wrong type from rpc.job.run'
        )
        self.failUnless(
            rpc.job.running(job),
            "%s should be running" % job
        )
        return job
    # end runWaitJob

    def stopWaitJob(self, uid):
        self.failUnlessEqual(
            rpc.job.running(uid), True,
            "job be running"
        )
        self.failUnlessEqual(
            rpc.job.kill(uid), True,
            "job.kill should return True"
        )
        self.failUnlessEqual(
            rpc.job.running(uid), False,
            "job should not be running"
        )
    # end stopWaitJob
# end Base

class Client(Base):
    def test_ping(self):
        self.failUnless(rpc.ping(), "ping should always return True")
    # end test_ping

    def test_online(self):
        self.failUnless(rpc.online(), "client reports it's not online")
        self.failIf(rpc.online(False), "client should now be offline")
        self.failIf(rpc.online(), "client reports it's online")
    # test_online

    def test_online_exception(self):
        self.failUnlessRaises(xmlrpc.Fault, rpc.online, -1)

        try:
            rpc.online(-1)

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 3,
                "expected fault code 3 not %i" % error.faultCode
            )
    # end test_online_exception

    def test_shutdown(self):
        self.runWaitJob()
        self.failUnlessRaises(xmlrpc.Fault, rpc.shutdown, False)

        try:
            rpc.shutdown()

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 9,
                "expected fault code 9 not %i" % error.faultCode
            )

        try:
            rpc.shutdown(True)

        except xmlrpc.Fault:
            self.fail("forced shutdown failed")
    # end test_shutdown

    def test_restart(self):
        self.runWaitJob()
        self.failUnlessRaises(xmlrpc.Fault, rpc.restart, False)

        try:
            rpc.restart()

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 9,
                "expected fault code 9 not %i" % error.faultCode
            )

        try:
            rpc.restart(True)

        except xmlrpc.Fault:
            self.fail("forced restart failed")
    # end test_restart

    def test_jobs_max(self):
        try:
            rpc.jobs_max(0)

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 8,
                "expected fault code 8 not %i" % error.faultCode
            )

        try:
            rpc.jobs_max(True)

        except Exception, error:
            self.failUnlessEqual(
                error.faultCode, 3,
                "expected fault code 3 not %i" % error.faultCode
            )
    # end test_jobs_max

    def test_free(self):
        jobs_max = rpc.jobs_max()
        for i in range(jobs_max):
            rpc.jobs_max()
            self.runWaitJob()

        self.failUnlessRaises(xmlrpc.Fault, self.runWaitJob, *[])
    # end test_free
# end Client

class Job(Base):
    def test_run(self):
        uid = rpc.job.run("python", "-c 'print True'")
        self.failUnless(
            isinstance(uid, types.StringType), "uid should be a string"
        )

        try:
            uid = uuid.UUID(uid)

        except ValueError:
            self.fail("failed to convert string to uuid object")
    # end test_run

    def test_run_exception(self):
        # ensure that an exception is raised
        # if the command is not found
        try:
            rpc.job.run("foobar", "")
            self.failIf("exception not raised for missing command")

        except xmlrpc.Fault, error:
            self.failUnlessEqual(
                error.faultCode, 1,
                "expected fault code 1 not %i" % error.faultCode
            )
    # end test_run_exception

    def test_kill(self):
        uid = self.runWaitJob()
        self.stopWaitJob(uid)
    # end test_kill

    def test_running(self):
        uid = self.runWaitJob()
        self.failUnlessEqual(
            rpc.job.running(uid), True,
            "job should be running"
        )
        self.stopWaitJob(uid)
        self.failUnlessEqual(
            rpc.job.running(uid), False,
            "job should not be running"
        )
    # end test_running

    def test_ram_use(self):
        uid = self.runWaitJob()
        self.failUnless(
            isinstance(rpc.job.ram_use(uid), types.FloatType),
            "value from ram is not a float"
        )
        self.stopWaitJob(uid)
        self.failUnless(
            isinstance(rpc.job.ram_use(uid), types.NoneType),
            "value from ram is not a float"
        )
    # end test_ram_use

    def test_cpu_times(self):
        uid = self.runWaitJob()
        for time in rpc.job.cpu_times(uid):
            self.failUnless(
                isinstance(time, types.FloatType),
                "expected a float"
            )
        self.stopWaitJob(uid)
        for time in rpc.job.cpu_times(uid):
            self.failUnless(
                isinstance(time, types.NoneType),
                "expected None"
            )
    # end test_cpu_times

    def test_ram_percent(self):
        uid = self.runWaitJob()
        self.failUnless(
            isinstance(rpc.job.ram_percent(uid), types.FloatType),
            "ram percent should be a float"
        )
        self.stopWaitJob(uid)
        self.failUnless(
            isinstance(rpc.job.ram_percent(uid), types.NoneType),
            "ram percent should be None"
        )
    # end test_ram_percent

    def test_cpu_percent(self):
        uid = self.runWaitJob()
        self.failUnless(
            isinstance(rpc.job.cpu_percent(uid), types.FloatType),
            "cpu percent should be a float"
        )
        self.stopWaitJob(uid)
        self.failUnless(
            isinstance(rpc.job.cpu_percent(uid), types.NoneType),
            "ram percent should be None"
        )
    # end test_cpu_percent

    def test_elapsed(self):
        uid = self.runWaitJob()
        start = rpc.job.elapsed(uid)
        end = rpc.job.elapsed(uid)
        self.failUnless(
            isinstance(start, types.FloatType), "expected float for start"
        )
        self.failUnless(
            isinstance(end, types.FloatType), "expected float for end"
        )
        self.failUnless(end > start, "expected time to increase")
    # end test_elapsed

    def test_exit_code(self):
        uid = self.runWaitJob()
        self.stopWaitJob(uid)
        self.failUnlessEqual(
            rpc.job.exit_code(uid), 1,
            "expected exit code 1"
        )
    # end test_exit_code
# end Job

if __name__ == '__main__':
    import sys
    sys.argv.append("-v")
    unittest.main()