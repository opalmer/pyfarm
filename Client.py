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
import os
import sys

# From PyFarm
from lib.Settings import ReadConfig
from lib.Logger import Logger
from lib.net import Qt4Reactor
from lib.net.udp.Broadcast import BroadcastSender, BroadcastReceiever
Qt4Reactor.install()

# From PyQt
from PyQt4.QtCore import QCoreApplication, QObject, SIGNAL, SLOT

# From Twisted
from twisted.internet import main
from twisted.internet import reactor
from twisted.internet.protocol import DatagramProtocol

__LOGLEVEL__ = 4
__MODULE__ = "ClientV2.py"

class Main(QObject):
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        self.config = ReadConfig("%s/cfg" % os.path.dirname(sys.argv[0]))
        self.log = Logger(__MODULE__)

    def listenForBroadcast(self):
        '''
        Step 1:
        Listen for an incoming broadcast from the master
        '''
        listen = BroadcastReceiever(self.config, self)
        self.connect(listen, SIGNAL("masterAddress"), self.setMasterAddress)
        listen.run()

    def setMasterAddress(self, data):
        '''
        Step 2:
        Set self.master to the incoming ip
        '''
        ip = data[1]
        self.log.netserver("Incoming Broadcast")
        self.log.netserver("Receieved master address: %s" % ip)

app = QCoreApplication(sys.argv)
main = Main(app)
main.listenForBroadcast()
app.exec_()