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
import os.path

# From PyFarm
from lib.Logger import Logger
from lib.Settings import ReadConfig
from lib.net.udp.Broadcast import BroadcastReceiever
from lib.net.tcp.Queue import QueueClient

# From PyQt
from PyQt4.QtCore import QCoreApplication, QObject, SIGNAL, SLOT

__LOGLEVEL__ = 4
__MODULE__ = "Client.py"
log = Logger(__MODULE__)

class Main(QObject):
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        self.config = ReadConfig(
                                            os.path.join(
                                                "%s" % os.path.dirname(__file__),
                                                "cfg",
                                                "general.ini"
                                            )
                                        )
        self.master = ''

    def listenForBroadcast(self):
        '''
        Step 1:
        Listen for an incoming broadcast from the master
        '''
        self.broadcast = BroadcastReceiever(self.config['servers']['broadcast'], parent=self)
        self.connect(self.broadcast, SIGNAL("masterAddress"), self.setMasterAddress)
        self.broadcast.run()

    def setMasterAddress(self, masterip):
        '''
        Step 2:
        Set self.master to the incoming ip
        '''
        if masterip[1] != self.master:
            self.master = masterip[1]
            log.netserver("Got master: %s" % self.master)
        #self.broadcast.quit()

        # inform the master of client computer
        queue = QueueClient(self.master, port=self.config['servers']['queue'])
        log.fixme('HARD CODED NETWORK HOSTNAME AND IP')
        queue.addClient('octo', '10.56.1.2')
        #heartbeat.sendPID('main', 'something', '5', '17838', '6')
        #heartbeat.sendRequest()


app = QCoreApplication(sys.argv)
log.debug("PID: %s" % os.getpid())
main = Main(app)
main.listenForBroadcast()
app.exec_()
