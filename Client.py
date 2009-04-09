#!/usr/bin/python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 31 2009
PURPOSE: To handle and run all client connections on a remote machine

    This file is part of PyFarm.
    Copyright (C) 2008-2009 Oliver Palmer

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
import os.path

# From PyQt
from PyQt4.QtCore import QObject, QCoreApplication, SIGNAL, SLOT
from PyQt4.QtNetwork import QHostInfo, QHostAddress

# From PyFarm
from lib.Que import *
from lib.Network import *
from lib.network.Management import BroadcastReceiever
from lib.network.Status import StatusClient
from lib.ReadSettings import ParseXmlSettings

settings = ParseXmlSettings('%s/settings.xml' % os.getcwd())

class Main(QObject):
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        self.master = ''
        self.ip = ''
        self.hostname = ''

        self.rendered = 0
        self.failed = 0
        self.software = {}

    def listenForBroadcast(self):
        '''Listen for an incoming broadcast from the master'''
        listen = BroadcastReceiever(self)
        self.connect(listen, SIGNAL("masterAddress"), self.setMasterAddress)
        listen.run()

    def setMasterAddress(self, masterip):
        '''Set self.master to the incoming ip'''
        if masterip != self.master:
            print "PyFarm :: network.management.BroadcastReceiever :: Incoming broadcast"
            print "PyFarm :: network.management.BroadcastReceiever :: Receieved master address: %s" % masterip
            self.master = masterip
            self.hostname = str(QHostInfo.localHostName())
            self.ip = str(QHostInfo.fromName(self.hostname).addresses()[0].toString())
            self.setInitialStatus()
            self.sendStatus()
        else:
            pass

    def setInitialStatus(self):
        '''Set the software dictionary and string'''
        systemInfo = System().os()
        os = systemInfo[0]
        arch = systemInfo[1]
        self.software = settings.installedSoftware()
        self.statusString = 'ip::%s,hostname::%s,os::%s,arch::%s%s' \
        % (self.ip, self.hostname, os, arch, settings.installedSoftware(stringOut=True))

    def sendStatus(self):
        '''Send new status information to the master'''
        client = StatusClient(self)

    def initSlave(self):
        '''Startup all servers and beging listening for connections'''
        # start the admin server
        self.admin = AdminServer(self)
        self.connect(self.admin, SIGNAL("SHUTDOWN"), self.shutdown)
        self.connect(self.admin, SIGNAL("RESTART"), self.restart)

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

app = QCoreApplication(sys.argv)
main = Main()
main.listenForBroadcast()
app.exec_()
