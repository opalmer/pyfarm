'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 11 2009
PURPOSE: Network modules related to the communication of standard out
and standard error logs between nodes.

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
# From PyQt
from PyQt4.QtCore import  QString, QObject, SIGNAL, QByteArray
from PyQt4.QtNetwork import QHostAddress, QUdpSocket

# From PyFarm
import lib.Logger as logger
from lib.ReadSettings import ParseXmlSettings

__MODULE__ = "lib.network.JobLogging"
settings = ParseXmlSettings('./cfg/settings.xml',  'cmd',  0, logger.LogMain(), logger.LEVELS)
UNIT16 = 8

class UdpLoggerClient(QObject):
    '''
    Logging client for standard output and
    standard error logs
    '''
    def __init__(self, master, port=settings.netPort('stdout'), parent=None):
        super(UdpLoggerClient, self).__init__(parent)
        self.master = master
        self.port = port
        self.parent = parent
        self.socket = QUdpSocket()
        self.socket.connectToHost(self.master, self.port)

    def writeLine(self, line):
        self.socket.writeDatagram(line, QHostAddress(self.master), self.port)
        self.log.log(NETCOMS,  "Sent: %s" % str(line))

class UdpLoggerServer(QUdpSocket):
    '''
    Logging server for standard output and
    standard error logs
    '''
    def __init__(self, logger, logLevels, port=settings.netPort('stdout'), parent=None):
        super(UdpLoggerServer, self).__init__(parent)
        # logging setup
        self.log = logger.moduleName("JobLogging.UdpLoggerServer")
        self.log.debug("UdpLoggerServer loaded")
        self.logLevels = logLevels

        self.port = port
        self.parent = parent
        self.bind(QHostAddress('0.0.0.0'), self.port)
        self.connect(self, SIGNAL("readyRead()"), self.readPendingDatagrams)
        self.log.log(self.logLevels["NETWORK"], "UDP job log server running on port %s" % self.port)

    def readPendingDatagrams(self):
        while self.hasPendingDatagrams():
            datagram = QByteArray()
            datagram.resize(self.pendingDatagramSize())
            sender = QHostAddress()
            port = 0
            data = self.readDatagram(datagram.size())
            self.emit(SIGNAL("incoming_line"), data)
            log("%s - %s" % (data[1].toString(), data[0]), 'devel1')
