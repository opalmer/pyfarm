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
cfg = os.path.join(
                    path,
                    "cfg",
                    "general.ini"
                  )

class Validate(unittest.TestCase):
    def setUp(self):
        self.cfg = ConfigParser.ConfigParser()
        self.cfg.read(cfg)
        self.section = "servers"
        self.pStart = 1025
        self.pEnd = 65535
        self.options = (
                            "broadcast",
                            "status",
                            "queue",
                            "logging",
                            "admin",
                            "hostinfo"
                        )

    def testHasSectionServers(self):
        '''Verify server section exists'''
        self.failIf(
                    not self.cfg.has_section(self.section),
                    "Section '%s' does not exist in cfg" % self.section
                   )

    def testHasAllOptions(self):
        '''Ensure all required options are present'''
        for option in self.options:
            self.failIf(
                            not self.cfg.has_option(self.section, option),
                            "Option '%s' was not present in %s" % (option, self.section)
                        )

    def testValidPortRange(self):
        '''Check for valid port range'''
        for option in self.options:
            value = self.cfg.getint(self.section, option)
            msg = "Port %i is out of range.  Please make sure all ports fall within: %i-%i" % (
                    value,
                    self.pStart,
                    self.pEnd
                    )

            self.failIf(
                            value not in range(self.pStart, self.pEnd+1),
                            msg
                        )

if __name__ == "__main__":
    unittest.main()