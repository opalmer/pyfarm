#!/usr/bin/env python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 26 2010
PURPOSE: To handle and run all client connections on a remote machine

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
# From Python
import os
import sys

# From PyFarm
from lib.Logger import Logger
from lib.Settings import ReadConfig
from lib.net.udp.Broadcast import BroadcastReceiever
from lib.net.tcp.Queue import QueueClient

# From PyQt
from PyQt4 import QtCore

LOGLEVEL = 4
MODULE = "Client.py"
log = Logger(MODULE)

class Main(QtCore.QObject):
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        self.config = ReadConfig(
                                    os.path.join(
                                        "%s" % os.path.dirname(__file__),
                                        "cfg",
                                        "general.ini"
                                    )
                                )

        self.masterHostname = ''
        self.masterAddress  = ''

    def listenForBroadcast(self):
        '''
        Step 1:
        Listen for an incoming broadcast from the master
        '''
        self.broadcast = BroadcastReceiever(self.config['servers']['broadcast'], parent=self)
        self.connect(self.broadcast, QtCore.SIGNAL("masterFound"), self.setMasterAddress)
        self.broadcast.run()

    def setMasterAddress(self, response):
        '''
        Step 2:
        Set self.master to the incoming ip
        '''
        newName = False
        newAddr = False

        # check for new hostname
        if response[0] != self.masterHostname:
            self.masterHostname  = response[0]
            newName              = True

        # check for new address
        if response[1] != self.masterAddress:
            self.masterAddress = response[1]
            newName            = True

        # if new hostname OR address, update log and
        #  refresh services
        if newName or newAddr:
            log.netserver(
                            "Got master: %s (%s)"
                            % (self.masterHostname, self.masterAddress)
                         )

            # inform the master of client computer
            queue = QueueClient(
                                    self.masterAddress,
                                    port=self.config['servers']['queue']
                                )
            queue.addClient(self.masterHostname, self.masterAddress)


if __name__ == '__main__':
    app = QtCore.QCoreApplication(sys.argv)
    log.debug("PID: %s" % os.getpid())
    main = Main(app)
    main.listenForBroadcast()
    app.exec_()
