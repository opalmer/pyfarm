'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 11 2009
PURPOSE: Network modules related to the communication of standard out
and standard error logs between nodes.

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
# From PyQt
from PyQt4.QtCore import QThread, QString, QObject
from PyQt4.QtCore import SIGNAL, QDataStream, QByteArray, QIODevice, SLOT
from PyQt4.QtNetwork import QAbstractSocket, QTcpSocket, QTcpServer, QUdpSocket, QHostAddress

# From PyFarm
from lib.ReadSettings import ParseXmlSettings
settings = ParseXmlSettings('settings.xml', skipSoftware=False)

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


class UdpLoggerServer(QUdpSocket):
    '''
    Logging server for standard output and
    standard error logs
    '''
    def __init__(self, port=settings.netPort('stdout'), parent=None):
        super(UdpLoggerServer, self).__init__(parent)
        self.modName = "UdpLoggerServer -- UdpLoggerServer"
        self.port = port
        self.parent = parent
        self.bind(QHostAddress('0.0.0.0'), self.port)
        self.connect(self, SIGNAL("readyRead()"), self.readPendingDatagrams)
        print "PyFarm :: %s :: Log server running" % self.modName

    def readPendingDatagrams(self):
        while self.hasPendingDatagrams():
            datagram = QByteArray()
            datagram.resize(self.pendingDatagramSize())
            sender = QHostAddress()
            port = 0
            data = self.readDatagram(datagram.size())
            print "%s - %s" % (data[1].toString(), data[0])
