#!/usr/bin/env python26
#
# PURPOSE: To import the standard includes and setup the package
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

import sys
import jsonrdp
from PyQt4 import QtNetwork, QtCore
from PyQt4.QtCore import Qt

PORT = 9407

class Main(QtCore.QObject):

    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        self.tcpServer = jsonrdp.Server(self)

        if not self.tcpServer.listen(QtNetwork.QHostAddress("0.0.0.0"), PORT):
            print "Error: %s" % self.tcpServer.errorString()
            self.close()
            return

if __name__ == "__main__":
    import signal
    signal.signal(signal.SIGINT, signal.SIG_DFL)

    app = QtCore.QCoreApplication(sys.argv)
    server = Main()
    app.exec_()