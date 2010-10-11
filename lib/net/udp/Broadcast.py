'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008
PURPOSE: Group of classes dedicated to the discovery and management
of remote hosts

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
import re
import sys
import socket
import traceback

# From PyQt
from PyQt4.QtNetwork import QUdpSocket, QHostAddress, QHostInfo
from PyQt4.QtCore import QThread, SIGNAL, QByteArray, QTimer, QString

# From PyFarm
from lib.Logger import Logger
from lib.Settings import ReadConfig

MODULE = "lib.net.udp.Broadcast"
LOGLEVEL = 4

class BroadcastSender(QThread):
    '''Class to send broadcast signals to client network'''
    def __init__(self, config, parent=None):
        super(BroadcastSender, self).__init__(parent)
        # setup some standard vars, so we dont broadcast forever
        self.config = config
        self.port = self.config['servers']['broadcast']
        self.count = self.config['broadcast']['interval']
        self.maxCount = self.config['broadcast']['maxcount']
        self.log = Logger("Broadcast.BroadcastSender",LOGLEVEL)

        # get all IPs
        self.addresses = ''
        self.hostname = str(QHostInfo.localHostName())
        isLocalhost = re.compile(r"""(127[.]0[.](?:0|1)[.]1)""")
        isIP = re.compile(r"""(\d{1,3}[.]\d{1,3}[.]\d{1,3}[.]\d{1,3})""")
        isSubnet = re.compile(r"""((?:255|0)[.](?:255|0)[.](?:255|0)[.](?:255|0))""")
        for address in [ str(addr.toString()) for addr in QHostInfo.fromName(self.hostname).addresses() ]:
            if not isLocalhost.match(address) and not isSubnet.match(address) and isIP.match(address):
                self.addresses += ","+address
        self.log.debug(self.addresses)

    def run(self):
        '''Start the broadcast thread and setup the outgoing connection'''
        self.log.netclient("Broadcasting")
        self.socket = QUdpSocket()
        self.datagram = QByteArray()
        self.send()

    def send(self):
        '''
        Send the broadcast packet so long as we have not exceeded
        the above specs.
        '''
        count = 0
        while count < self.maxCount:
            self.log.debug("Sending broadcast")
            self.datagram.clear()
            self.datagram.insert(0, self.addresses)
            self.socket.writeDatagram(self.datagram.data(), QHostAddress.Broadcast, self.port)
            count += 1
        self.quit()

    def quit(self):
        '''End the process and kill the thread'''
        self.log.netclient("Stopping broadcast")
        self.exit(0)


class BroadcastReceiever(QThread):
    '''Class to receieve broadcast signal from master'''
    def __init__(self, port, parent=None):
        super(BroadcastReceiever, self).__init__(parent)
        self.log = Logger("Broadcast.BroadcastReceiever",LOGLEVEL)
        self.log.netserver("Running")
        self.port =port

    def readIncomingBroadcast(self):
        '''Read the incoming host ip and emit it to the client'''
        self.log.netserver("Incoming broadcast")
        while self.socket.hasPendingDatagrams():
            datagram = QByteArray()
            datagram.resize(self.socket.pendingDatagramSize())
            sender = QHostAddress()
            data = self.socket.readDatagram(datagram.size())
            ip = str(data[1].toString())
            msg = str(data[0])

        self.log.debug("Master: %s" % ip)
        self.emit(SIGNAL("masterAddress"), (msg, ip))

    def run(self):
        '''Run the main thread and listen for connections'''
        self.socket = QUdpSocket()
        self.connect(self.socket, SIGNAL("readyRead()"), self.readIncomingBroadcast)
        self.socket.bind(QHostAddress.Any, self.port)
        self.log.netserver("Running")

    def quit(self):
        '''Stop the broadcast receiever'''
        self.log.netserver("Stopped")
        self.exit(0)