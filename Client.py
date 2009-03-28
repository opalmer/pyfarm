#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Jan 31 2009
PURPOSE: To handle and run all client connections on a remote machine

    This file is part of PyFarm.

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

import sys
import os.path

from lib.Network import *
from lib.Que import *
from PyQt4.QtCore import *

from lib.ReadSettings import ParseXmlSettings

settings = ParseXmlSettings('%s/settings.xml' % os.getcwd(), skipSoftware=True)

class Main(QObject):
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        # check and see if the user has requested
        #  a local only client
        try:
            if sys.argv[1] == 'local':
                self.LOCAL = True
        except IndexError:
            self.LOCAL = False

    def startBroadcast(self):
        if not self.LOCAL:
            broadcast = BroadcastClient()
            self.master = broadcast.run()
            self.localhost = GetLocalIP(self.master)
            self.initSlave()
        else:
            self.localhost = '127.0.0.1'
            self.initSlave()

    def initSlave(self):
        '''Startup all servers and beging listening for connections'''
        # start the admin server
        self.admin = AdminServer(self)
        self.connect(self.admin, SIGNAL("SHUTDOWN"), self.shutdown)
        self.connect(self.admin, SIGNAL("RESTART"), self.restart)
        self.connect(self.admin, SIGNAL("HALT"), self.halt)

        if not self.admin.listen(QHostAddress(self.localhost), settings.netPort('admin')):
            print "PyFarm :: Client.AdminServer :: Could not start the server: %s" % self.admin.errorString()
            return
        print "PyFarm :: Client.AdminServer :: Waiting for signals..."

        # start the que server
        self.que = QueSlaveServer(self)
        if not self.que.listen(QHostAddress(self.localhost), settings.netPort('que')):
            print "PyFarm :: Client.QueSlave :: Could not start the server: %s" % self.que.errorString()
            return
        print "PyFarm :: Client.QueSlave :: Waiting for jobs..."

    def shutdownServers(self):
        '''Calls the shutdown function all all servers'''
        self.que.shutdown()
        self.admin.shutdown()

    def restart(self):
        '''Close all connections and restart the client'''
        self.shutdownServers()
        self.startBroadcast()

    def shutdown(self):
        '''Close all connections and shutdown the client'''
        print "PyFarm :: Client :: Got shutdown signal from Admin Server"
        self.shutdownServers()
        sys.exit("PyFarm :: Client :: Client Shutdown by Admin")

    def halt(self):
        '''Close all connections and halt the client'''
        print "SYSTEM HALTED -- NEEDS IMPLIMENTATION"

app = QCoreApplication(sys.argv)
main = Main()
main.startBroadcast()
app.exec_()
