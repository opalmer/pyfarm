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

class Admin(QObject):
    '''Administrator class used to manage the client.'''
    def __init__(self, parent=None):
        super(Admin, self).__init__(parent)
        self.admin = AdminServer()
        self.connect(self.admin, SIGNAL("RESTART"), self.emit)
        self.connect(self.admin, SIGNAL("SHUTDOWN"), self.emit)

    def Restart(self):
        '''Inform the client to restart'''
        self.emit(SIGNAL("RESTART"))

    def Shutdown(self):
        '''Shutdown the client and close all connections'''
        self.emit(SIGNAL("SHUTDOWN"))

    def boot(self, address):
        '''Start the admin server'''
        if not self.admin.listen(QHostAddress(self.listen), ADMIN_PORT):
            print "Socket Error: %s " % self.socket.errorString()
        print "Admin Server Running..."

    def kill(self):
        '''Kill the current admin server'''
        print "Closing the admin server..."
        self.socket.close()
        print "Admin server closed!"

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

        self.admin = Admin()
        self.connect(self.admin, SIGNAL("RESTART"), self.RestartClient)
        self.connect(self.admin, SIGNAL("SHUTDOWN"), self.Shutdown)

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
        self.admin.boot(self.localhost)

        # start the que server
        self.socket = QueSlaveServer(self)
        if not self.socket.listen(QHostAddress(self.localhost), QUE_PORT):
            print "Socket Error: %s " % self.socket.errorString()
        print "Que server waiting for jobs..."

    def RestartClient(self):
        '''Close all connections and restart the client'''
        self.socket.close()
        self.startBroadcast()

    def Shutdown(self):
        '''Close all connections and shutdown the client'''
        self.socket.close()
        sys.exit("Client Shutdown Via Admin")

app = QCoreApplication(sys.argv)
main = Main()
main.startBroadcast()
app.processEvents()
app.exec_()
