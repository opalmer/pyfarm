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
import traceback

from lib.Network import *
from lib.Que import *
from PyQt4.QtCore import *

# port settings
SIZEOF_UINT16 = Settings.Network().Unit16Size()
BROADCAST_PORT = Settings.Network().BroadcastPort()
QUE_PORT = Settings.Network().QuePort()
STDOUT_PORT = Settings.Network().StdOutPort()
STDERR_PORT = Settings.Network().StdErrPort()
ADMIN_PORT = Settings.Network().Admin()
USE_STATIC_CLIENT = False

class StartAdminServer(QObject):
    '''
    Main function that spawns admin servers and other functions
    and waits for their shutdown/restart/halt signals
    '''
    def __init__(self, parent=None):
        super(StartAdminServer, self).__init__(parent)

        self.admin = AdminServer(self)
        self.connect(self.admin, SIGNAL("SHUTDOWN"), self.shutdown)
        self.connect(self.admin, SIGNAL("RESTART"), self.restart)
        self.connect(self.admin, SIGNAL("HALT"), self.halt)

        if not self.admin.listen(QHostAddress("0.0.0.0"), ADMIN_PORT):
            print "PyFarm :: Main.AdminServer :: Could not start the server"
            return

    def shutdown(self):
        '''If the admin servers calls for it, shutdown the client'''
        self.admin.close()
        sys.exit("PyFarm :: Network.AdminMain :: Closed by Admin Server")

    def restart(self):
        '''If the admin servers calls for it, restart the client'''
        pass

    def halt(self):
        '''If the admin servers calls for it, half the client'''
        pass


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

        if not self.admin.listen(QHostAddress(self.localhost), ADMIN_PORT):
            print "PyFarm :: Client.AdminServer :: Could not start the server: %s" % self.admin.errorString()
            return
        print "PyFarm :: Client.AdminServer :: Waiting for signals..."

        # start the que server
        self.que = QueSlaveServer(self)
        if not self.que.listen(QHostAddress(self.localhost), QUE_PORT):
            print "PyFarm :: Client.QueSlave :: Could not start the server: %s" % self.que.errorString()
            return

        print "PyFarm :: Client.QueSlave :: Waiting for jobs..."

    def shutdownServers(self):
        '''Calls the shutdown function all all servers'''
        self.admin.shutdown()
        self.que.shutdown()

    def restart(self):
        '''Close all connections and restart the client'''
        self.shutdown()
        self.startBroadcast()

    def shutdown(self):
        '''Close all connections and shutdown the client'''
        self.shutdownServers()
        sys.exit("PyFarm :: Client :: Client Shutdown by Admin")

    def halt(self):
        '''Close all connections and halt the client'''
        print "SYSTEM HALTED -- NEEDS IMPLIMENTATION"

app = QCoreApplication(sys.argv)
main = Main()
main.startBroadcast()
#app.processEvents()
app.exec_()
