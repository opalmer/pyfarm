#!/usr/bin/python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 26 2010
PURPOSE: To handle and run all client connections on a remote machine

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
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
# From Python
import sys

# From PyQt
from PyQt4.QtCore import QCoreApplication, QObject

# From PyFarm
from lib.Logger import Logger
from lib.net.Qt4Reactor import QTReactor

# From Twisted
from twisted.internet import main
from twisted.internet.protocol import DatagramProtocol
from twisted.application.internet import MulticastServer

__LOGLEVEL__ = 4

class MulticastClientUDP(DatagramProtocol):

    def datagramReceived(self, datagram, address):
            print "Received:" + repr(datagram)

#reactor.run()

class Main(QObject):
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        reactor.listenUDP(0, MulticastClientUDP()).write('UniqueID', ('10.56.1.2', 8005))


app = QCoreApplication(sys.argv)
reactor = QTReactor(app=app)
main.installReactor(reactor)
main = Main(app)
app.exec_()
