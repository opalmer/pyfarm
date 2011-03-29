'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008
PURPOSE: Group of classes dedicated to the discovery and management
of remote hosts

    This file is part of PyFarm.
    Copyright (C) 2008-2011 Oliver Palmer

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
import os
import re
import sys
import socket
import traceback

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import logger, settings, system

logger = logger.Logger()

class BroadcastSender(QtCore.QThread):
    '''Class to send broadcast signals to client network'''
    def __init__(self, config, parent=None):
        super(BroadcastSender, self).__init__(parent)
        # setup some standard vars, so we dont broadcast forever
        addresses     = []
        self.config   = config
        self.port     = self.config['servers']['broadcast']
        self.count    = self.config['broadcast']['interval']
        self.maxCount = self.config['broadcast']['maxcount']
        self.netinfo  = system.info.Network()
        self.local    = QtNetwork.QHostAddress()

    def run(self):
        '''Start the broadcast thread and setup the outgoing connection'''
        logger.netclient("Broadcasting")
        self.socket   = QtNetwork.QUdpSocket()
        self.datagram = QtCore.QByteArray()
        self.send()

    def send(self):
        '''
        Send the broadcast packet so long as we have not exceeded
        the above specs.
        '''
        count = 0

        # send a broadcast so long as we don't
        #  exceed the set max.
        while count < self.maxCount:
            logger.debug("Sending broadcast")
            self.datagram.clear()
            self.datagram.insert(0, self.netinfo.hostname())
            self.socket.writeDatagram(self.datagram.data(), self.local, self.port)
            count += 1
        self.quit()

    def quit(self):
        '''End the process and kill the thread'''
        logger.netclient("Stopping broadcast")
        self.exit(0)


class BroadcastReceiever(QtCore.QThread):
    '''Class to receieve broadcast signal from master'''
    def __init__(self, port, parent=None):
        super(BroadcastReceiever, self).__init__(parent)
        self.port  = port
        self.local = QtNetwork.QHostAddress()
        logger.netserver("Running")

    def run(self):
        '''Run the main thread and listen for connections'''
        self.socket = QtNetwork.QUdpSocket()
        self.connect(self.socket, QtCore.SIGNAL("readyRead()"), self.readIncomingBroadcast)
        self.socket.bind(self.local, self.port)
        logger.netserver("Running")

    def readIncomingBroadcast(self):
        '''Read the incoming host ip and emit it to the client'''
        logger.netserver("Incoming broadcast")

        while self.socket.hasPendingDatagrams():
            datagram = QtCore.QByteArray()
            datagram.resize(self.socket.pendingDatagramSize())

            sender = QtNetwork.QHostAddress()
            data   = self.socket.readDatagram(datagram.size())
            ip     = str(data[1].toString())
            msg    = str(data[0])

        logger.netclient("Host: %s IP: %s" % (msg, ip))
        self.emit(QtCore.SIGNAL("masterFound"), (msg, ip))

    def quit(self):
        '''Stop the broadcast receiever'''
        logger.netserver("Stopped")
        self.exit(0)

# cleanup objects
del CWD, PYFARM
