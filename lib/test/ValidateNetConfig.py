#!/usr/bin/python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Aug 11 2010
PURPOSE: Runs test suite on network config file to verify that it's
setup properly

This file is part of PyFarm.
Copyright (C) 2008-2010 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
'''
import os
import sys
import os.path
import unittest
import ConfigParser

rollback = 2
path = os.path.dirname(os.path.abspath(__file__))
for i in range(rollback): path = os.path.dirname(path)
sys.path.append(path)

import lib.net

class Validate(unittest.TestCase):
    def setUp(self):
        self.cfg = ConfigParser.ConfigParser()
        self.cfg.read(
                            os.path.join(
                                                path,
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

    def testDNSHostname(self):
        '''Compare hostname to DNS'''
        ip = lib.net.ip()
        hostname = lib.net.hostname()
        dnsHostname = lib.net.lookupHostname(ip)
        self.failIf(
                        hostname != dnsHostname,
                        "DNS Hostname does not match local hostname"
                    )

    def testDNSIp(self):
        '''Compare ip address to DNS'''
        ip = lib.net.ip()
        hostname = lib.net.hostname()
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
