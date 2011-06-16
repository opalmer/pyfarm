#!/usr/bin/env python
#
# INITIAL: Aug 11 2010
# PURPOSE: Runs test suite on network config file to verify that it's
#          setup properly
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
import site
import unittest
import ConfigParser

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(CWD, "..", ".."))
site.addsitedir(root)

import lib.net
from lib.system import info

class Validate(unittest.TestCase):
    def setUp(self):
        self.netinfo = Info.Network()
        self.serverOptions = (
            "broadcast", "status", "queue", "logging",
            "admin", "hostinfo"
        )
        self.cfg = ConfigParser.ConfigParser()
        self.cfg.read(
                            os.path.join(
                                                PYFARM,
                                                "cfg",
                                                "general.ini"
                                            )
                        )

    def testConfigHasSection(self):
        '''Ensure configuration has required section'''
        self.failIf(
                        self.cfg.has_section("servers") == False,
                        "Your configuration is missing the servers section"
                    )


    def testConfigHasServerCount(self):
        '''Ensure configuration has required option'''
        self.failIf(
                        self.cfg.has_option("servers", "count") == False,
                        "Your configuration is missing the server count"
                    )

    def testCheckForPortOptions(self):
        '''Test for custom port values'''
        for value in self.serverOptions:
            self.failIf(
                            self.cfg.has_option("servers", value) == False,
                            "Server configuration is missing the %s option" % value
                        )

    def testVerifyPortOptions(self):
        '''Verify custom port values'''
        portRange = range(1025, 65536)

        for value in self.serverOptions:
            try:
                port = self.cfg.getint("servers",  value)
                self.failIf(
                                port not in portRange,
                                "Invalid port for %s, must be in range: 1025-65535" % value
                            )

            except ValueError:
                self.fail("Port option %s does not contain a valid entry.  Entries must either be int or None" % value)

    def testDNSHostname(self):
        '''Compare hostname to DNS'''
        ip = self.netinfo.ip()
        hostname = self.netinfo.hostname()
        dnsHostname = lib.net.lookupHostname(ip)
        self.failIf(
                        hostname != dnsHostname,
                        "DNS Hostname does not match local hostname"
                    )

    def testDNSIp(self):
        '''Compare ip address to DNS'''
        ip = self.netinfo.ip()
        hostname = self.netinfo.hostname()
        dnsIp = lib.net.lookupAddress(hostname)
        self.failIf(
                        ip != dnsIp,
                        "DNS Address does not match local address"
                    )

    def testOpenPorts(self):
        '''Check for open ports'''
        ports = []
        count = self.cfg.getint("servers", "count")
        for i in range(count):
            port = lib.net.getPort()
            if port not in ports:
                ports.append(port)
            else:
                self.fail("Attempted to add port %i twice" % ports)

        self.failIf(
                        len(ports) != count,
                        "Only found %i open ports, %i is required" % (len(ports),  count)
                    )

if __name__ == "__main__":
    unittest.main()
