# No shebang line, this module is meant to be imported
#
# INITIAL:  April 7 2008
# PURPOSE: Group of classes dedicated to the discovery and management
#          of remote hosts
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
import re
import time
import site
import socket
import traceback

__all__ = ["BroadcastSender", "BroadcastReceiever"]

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, "..", "..", ".."))
site.addsitedir(root)

from PyQt4 import QtCore, QtNetwork

from lib import logger, settings, system, net

logger = logger.Logger()

class BroadcastSender(QtCore.QThread):
    '''Class to send broadcast signals to client network'''
    def __init__(self, config, services, parent=None):
        super(BroadcastSender, self).__init__(parent)
        # setup some standard vars, so we dont broadcast forever
        addresses = []
        self.services = services
        self.port = config['servers']['broadcast']
        self.interval = config['broadcast']['interval']
        self.duration = config['broadcast']['duration']
        self.netinfo = system.info.Network()

    def run(self):
        '''Start the broadcast thread and setup the outgoing connection'''
        logger.netclient("Starting Broadcast")
        self.socket = QtNetwork.QUdpSocket()
        self.datagram = QtCore.QByteArray()
        self.send()

    def send(self):
        '''
        Send the broadcast packet so long as we have not exceeded
        the above specs.
        '''
        self.stopBroadcast = False
        services = self.services.toString()
        start = time.time()
        stop = start + self.duration
        broadcast = QtNetwork.QHostAddress("255.255.255.255")

        while time.time() < stop:
            if self.stopBroadcast:
                break

            self.datagram.clear()
            self.datagram.insert(0, self.netinfo.hostname())

            # send the frame
            self.socket.writeDatagram(
                                        services,
                                        broadcast,
                                        self.port
                                      )

            # emit a broadcast signal for progress then sleep
            self.emit(QtCore.SIGNAL("broadcast"))
            time.sleep(self.interval)

        self.emit(QtCore.SIGNAL("complete"))
        self.quit()

    def quit(self):
        '''End the process and kill the thread'''
        self.stopBroadcast = True
        logger.netclient("Stopping Broadcast")
        self.exit(0)


class BroadcastReceiever(QtCore.QThread):
    '''Class to receieve broadcast signal from master'''
    def __init__(self, port, parent=None):
        super(BroadcastReceiever, self).__init__(parent)
        self.port = port
        logger.netserver("Running")

    def run(self):
        '''Run the main thread and listen for connections'''
        self.socket = QtNetwork.QUdpSocket()
        self.connect(
                        self.socket,
                        QtCore.SIGNAL("readyRead()"),
                        self.readIncomingBroadcast
                    )
        self.socket.bind(self.port)
        logger.netserver("Running")

    def readIncomingBroadcast(self):
        '''Read the incoming host ip and emit it to the client'''

        while self.socket.hasPendingDatagrams():
            datagram = QtCore.QByteArray()
            datagram.resize(self.socket.pendingDatagramSize())

            sender = QtNetwork.QHostAddress()
            data = self.socket.readDatagram(datagram.size())
            packet = str(data[0])
            msg = net.Services.fromString(packet)

            # unpacked packet
            ip = str(data[1].toString())
            host = msg[0]
            services = msg[2]

        self.emit(QtCore.SIGNAL("broadcast"), (host, ip, services))

    def quit(self):
        '''Stop the broadcast receiever'''
        logger.netserver("Stopped")
        self.exit(0)
