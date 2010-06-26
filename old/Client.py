#!/usr/bin/python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 31 2009
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
from time import time
from os.path import dirname

# From PyQt
from PyQt4.QtCore import QObject, QDir, QString, QFileInfo
from PyQt4.QtCore import QCoreApplication, SIGNAL, SLOT
from PyQt4.QtNetwork import QHostInfo, QHostAddress

__MODULE__ = "Client.py"
__LOGLEVEL__ = 4

wd = dirname(str(QDir(sys.argv[0]).canonicalPath()))
QDir().setCurrent(wd)

# From PyFarm
from lib.Logger import Logger
from lib.Info import System
from lib.network.Utils import GetLocalIP
from lib.network.Status import StatusClient
from lib.network.Que import QueSlaveServer
from lib.network.Admin import AdminServer
from lib.ReadSettings import ParseXmlSettings
from lib.network.Broadcast import BroadcastReceiever

settings = ParseXmlSettings('./cfg/settings.xml',  'cmd',  skipSoftware=True)
log = Logger(__MODULE__, __LOGLEVEL__)

class BroadcastManager(object):
    def __init__(self):
        self.running = 0
        self.object = None

    def setRunning(self, obj):
        '''Set running equal to 1'''
        self.running = 1
        self.object = obj

    def setStopped(self):
        '''Set running equal to 0'''
        self.running = 0


class AdminManager(object):
    def __init__(self):
        self.running = 0
        self.object = None

    def setRunning(self, obj):
        '''Set running equal to 1'''
        self.running = 1
        self.object = obj

    def setStopped(self):
        '''Set running equal to 0'''
        self.running = 0


class QueManager(object):
    def __init__(self):
        self.running = 0
        self.object = None

    def setRunning(self, obj):
        '''Set running equal to 1'''
        self.running = 1
        self.object = obj

    def setStopped(self):
        '''Set running equal to 0'''
        self.running = 0


class ServerManager(object):
    '''
    Simple class to help control and inform the program
    of the servers
    '''
    def __init__(self):
        self.broadcast = BroadcastManager()
        self.admin = AdminManager()
        self.que = QueManager()
        self.servers = {
                      "broadcast" : self.broadcast,
                      "admin" : self.admin,
                      "que" : self.que,
                      }

        log.netserver("ServerManager Running")


class Main(QObject):
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        self.master = ''
        self.hostname = ''
        self.ip = ''
        self.sysInfo = ''
        self.rendered = 0
        self.failed = 0
        self.software = {}
        self.servers = ServerManager()
        self.session = None
        log.debug("Client Initiated")
        self.logAdmin = Logger("AdminServer")
        self.logQueue = Logger("Queue")

    def setVarDefaults(self):
        '''Return the input vars to their initial states'''
        self.master = ''
        self.hostname = ''
        self.ip = ''
        self.sysInfo = ''
        self.software = {}

    def listenForBroadcast(self):
        '''
        Step 1:
        Listen for an incoming broadcast from the master
        '''
        listen = BroadcastReceiever(self)
        self.connect(listen, SIGNAL("masterAddress"), self.setMasterAddress)
        listen.run()
        self.servers.broadcast.setRunning(listen)

    def setMasterAddress(self, data):
        '''
        Step 2:
        Set self.master to the incoming ip
        '''
        if self.uniqueSession(data[0]):
            ip = data[1]
            if ip != self.master:
                log.netserver("Incoming Broadcast")
                log.netserver("Receieved master address: %s" % ip)
                self.master = ip
                self.hostname = str(QHostInfo.localHostName())
                self.ip = GetLocalIP(self.master)
                self.setInitialStatus()
                self.sendStatus()
                self.initial = int(time())
            elif ip == self.master:
                log.netserver("Incoming Broadcast")
                self.sendStatus()

    def uniqueSession(self, uuid):
        if uuid != self.session:
            self.session = uuid
            return True
        else:
            return False

    def setInitialStatus(self):
        '''
        Step 3:
        Set the software dictionary and string
        '''
        systemInfo = System().os()
        os = systemInfo[0]
        arch = systemInfo[1]
        self.software = settings.installedSoftware()
        self.sysInfo = 'ip::%s,hostname::%s,os::%s,arch::%s%s' \
        % (self.ip, self.hostname, os, arch, settings.installedSoftware(stringOut=True))

    def sendStatus(self):
        '''
        Step 4:
        Send new status information to the master
        '''
        client = StatusClient(self.master, settings.netPort('status'), self)
        self.connect(client, SIGNAL('MASTER_CONNECTED'), self.initSlave)
        client.updateMaster('INIT', self.sysInfo)

    def initSlave(self):
        '''Startup all servers and beging listening for connections'''
        # start the admin server
        self.admin = AdminServer(self)
        self.connect(self.admin, SIGNAL("SHUTDOWN"), self.shutdown)
        self.connect(self.admin, SIGNAL("RESTART"), self.restart)

        if not self.admin.listen(QHostAddress('0.0.0.0'), settings.netPort('admin')):
            if self.servers.admin.running:
                self.logAdmin.warning("Server is already running")
            else:
                self.logAdmin.error("Could not start the server: %s" % self.admin.errorString())
            self.logAdmin.netserver("Server is running")
        else:
            self.servers.admin.setRunning(self.admin)
            self.logAdmin.netserver("Waiting for signals...")

        # start the que server
        self.que = QueSlaveServer(self.master, parent=self)

        if not self.que.listen(QHostAddress('0.0.0.0'), settings.netPort('que')):
            if self.servers.que.running:
                self.logQueue.warning("Server is already running")
            else:
                self.logQueue.error("Could not start the server: %s" % self.que.errorString())
        else:
            self.servers.que.setRunning(self.que)
            self.logQueue.debug("Server is running")

    def shutdownServers(self):
        '''Calls the shutdown function all all servers'''
        if self.servers.admin.running:
            self.admin.shutdown()

        if self.servers.que.running:
            self.que.shutdown()

    def restart(self):
        '''Close all connections and restart the client'''
        log.info("Restarting client")
        self.shutdownServers()
        self.setVarDefaults()
        log.netserver("Listening for broadcast")
        log.debug("Restart sequence complete")

    def shutdown(self):
        '''Close all connections and shutdown the client'''
        log.warning("Got shutdown signal from admin server")
        self.shutdownServers()
        log.fatal("Client shutdown by admin")

log.debug("Starting QCoreApplication")
app = QCoreApplication(sys.argv)
main = Main(app)
main.listenForBroadcast()
log.debug("Client Running")
app.exec_()
