'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008
PURPOSE: Group of classes dedicated to the discovery and management
of remote hosts

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
import socket
import traceback

# From PyQt
from PyQt4.QtNetwork import QUdpSocket, QHostAddress
from PyQt4.QtCore import QThread, SIGNAL, QByteArray, QTimer, QString

# From PyFarm
from lib.Logger import Logger
from lib.Settings import ReadConfig

__MODULE__ = "lib.net.udp.Broadcast"
__LOGLEVEL__ = 4

class BroadcastSender(QThread):
    '''Class to send broadcast signals to client network'''
    def __init__(self, config, parent=None):
        super(BroadcastSender, self).__init__(parent)
        self.log = Logger("Broadcast.BroadcastSender",__LOGLEVEL__)
        self.config = config
        # setup some standard vars, so we dont broadcast forever
        self.interval = self.config.broadcast['interval']
        self.maxCount = self.config.broadcast['maxcount']
        self.modName = 'BroadcastSender'

    def run(self):
        '''Start the broadcast thread and setup the outgoing connection'''
        self.log.netclient("Sending broadcast")
        self.socket = QUdpSocket()
        self.datagram = QByteArray()
        self.connect(self.socket, SIGNAL("readyRead()"), self.readIncomingBroadcast)

        # timer setup
        self.timer = QTimer()
        self.connect(self.timer, SIGNAL("timeout()"), self.send)
        self.timer.start(self.interval)

    def readIncomingBroadcast(self):
        self.log.netserver("Incoming broadcast reply")
        while self.socket.hasPendingDatagrams():
            datagram = QByteArray()
            datagram.resize(self.socket.pendingDatagramSize())
            sender = QHostAddress()
            data = self.socket.readDatagram(datagram.size())
            ip = str(data[1].toString())
            msg = str(data[0])
            print ip

    def send(self):
        '''
        Send the broadcast packet so long as we have not exceeded
        the above specs.
        '''
        self.count = 0
        if self.count > self.maxCount:
            self.emit(SIGNAL("done"), 0)
            self.quit()
        else:
            self.datagram.clear() # if we do not clear first the datagram will be appended to
            self.datagram.insert(0, QString("Hello, from master"))
            self.socket.writeDatagram(self.datagram.data(), QHostAddress("255.255.255.255"), self.config.servers['broadcast'])
            self.count += 1
            self.emit(SIGNAL("next"))

    def quit(self):
        '''End the process and kill the thread'''
        self.log.netclient("Stopping broadcast")
        self.timer.stop()
        self.exit(0)

# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!
# Broadcast receiever now sends reply ------ Sender still needs to listen!!!

class BroadcastReceiever(QThread):
    '''Class to receieve broadcast signal from master'''
    def __init__(self, config, parent=None):
        super(BroadcastReceiever, self).__init__(parent)
        self.modName = 'BroadcastReceiever'
        self.log = Logger("Broadcast.BroadcastReceiever")
        self.log.netserver("Listening for broadcast")
        self.config = config

    def readIncomingBroadcast(self):
        '''Read the incoming host ip and emit it to the client'''
        self.log.netserver("Incoming broadcast")
        while self.socket.hasPendingDatagrams():
            datagram = QByteArray()
            datagram.resize(self.socket.pendingDatagramSize())
            sender = QHostAddress()
            data = self.socket.readDatagram(datagram.size())
            ip = str(data[1].toString())
            self.master = ip
            msg = str(data[0])
            self.log.debug("Got master address: %s" % self.master)
            self.send()
        self.emit(SIGNAL("masterAddress"), (msg, ip))

    def send(self):
        '''
        Send the broadcast packet so long as we have not exceeded
        the above specs.
        '''
        self.log.netclient("Preparing to send reply")
        datagram = QByteArray()
        datagram.clear() # if we do not clear first the datagram will be appended to
        datagram.insert(0, QString("TEST"))
        self.socket.writeDatagram(datagram.data(), QHostAddress(self.master), self.config.servers['broadcast'])
        self.log.netclient("Reply sent")

    def run(self):
        '''Run the main thread and listen for connections'''
        self.socket = QUdpSocket()
        self.connect(self.socket, SIGNAL("readyRead()"), self.readIncomingBroadcast)
        self.socket.bind(QHostAddress('0.0.0.0'), self.config.servers['broadcast'])
