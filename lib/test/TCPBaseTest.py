#!/usr/bin/env python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Sept 04 2010
PURPOSE: Runs test suite on network config file to verify that it's
setup properly

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys
import unittest

from PyQt4 import QtCore
from PyQt4.Qt import Qt

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib.net.tcp import Request, Server

class TCPClientTest(unittest.TestCase, QtCore.QObject):
    def __init__(self, parent=None):
        super(TCPClientTest, self).__init__(parent)

    def setUp(self):
        self.client = Request(
                              "TEST_REQUEST",
                              ("v1", "v2", "v3", "v4")
                              )
        self.client.send("localhost", 27965)

    def testsetup(self):
        '''Basic client setup test'''
        pass

class TCPServerTest(unittest.TestCase, QtCore.QObject):
    def __init__(self, parent=None):
        super(TCPServerTest, self).__init__(parent)

    def setUp(self):
        self.server = Server()

    def testsetup(self):
        '''Basic server setup test'''
        pass


if __name__ == "__main__":
    if len(sys.argv) == 2 and sys.argv[1].lower() in ("client",  "server"):
        app = QtCore.QCoreApplication(sys.argv)

        # select the proper test
        if sys.argv[1].lower() == "client":
            test = unittest.TestLoader().loadTestsFromTestCase(TCPClientTest)
        elif sys.argv[1].lower() == "server":
            test = unittest.TestLoader().loadTestsFromTestCase(TCPServerTest)

        unittest.TextTestRunner(verbosity=2).run(test)
        app.exec_()
    else:
        sys.exit("Invalid input or number of input arguments")
