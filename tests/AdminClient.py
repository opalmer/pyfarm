#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: March 16 2009
PURPOSE: To design and test the admin client in a clean working environment

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
from PyQt4.QtCore import *
from PyQt4.QtNetwork import *

MAC = "qt_mac_set_native_menubar" in dir()

PORT = 9407
SIZEOF_UINT16 = 2


class AdminClient(QObject):
    def __init__(self, parent=None):
        super(AdminClient, self).__init__(parent)
        self.modName = 'Network.AdminClient'

        self.socket = QTcpSocket()
        self.client = "localhost"
        self.nextBlockSize = 0
        self.request = None

        self.connect(self.socket, SIGNAL("connected()"),self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def shutdown(self):
        '''Shutdown the client'''
        self.issueRequest(QString("SHUTDOWN"), QString("all processes"))

    def restart(self):
        '''Restart the client'''
        self.issueRequest(QString("RESTART"), QString("kill renders"))

    def halt(self):
        '''Hault the client but do not restart or shutdown'''
        self.issueRequest(QString("HALT"), QString("kill renders"))

    def issueRequest(self, action, options):
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << options
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)

        if self.socket.isOpen():
            print "PyFarm :: %s :: Already connected, closing socket" % self.modName
            self.socket.close()

        print "PyFarm :: %s :: Connecting to server" % self.modName
        self.socket.connectToHost(self.client, PORT)

    def sendRequest(self):
        print "PyFarm :: %s :: Sending request to server" % self.modName
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        print "PyFarm :: %s :: Reading response" % self.modName
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < SIZEOF_UINT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break
            action = QString()
            options = QString()
            stream >> action
            self.nextBlockSize = 0

            if action in ("SHUTDOWN", "RESTART", "HALT"):
                if action == "SHUTDOWN":
                    print "PyFarm :: %s :: %s is shutting down" % (self.modName, self.client)
                elif action == "RESTART":
                    print "PyFarm :: %s :: %s is restarting" % (self.modName, self.client)
                elif action == "HALT":
                    print "PyFarm :: %s :: %s is halting" % (self.modName, self.client)

    def serverHasStopped(self):
        print "PyFarm :: %s :: Server has stopped" % self.modName
        self.socket.close()

    def serverHasError(self, error):
        print "PyFarm :: %s :: Server has error: %s" % (self.modName, self.socket.errorString())
        self.socket.close()


app = QCoreApplication(sys.argv)
Client = AdminClient()
Client.shutdown()
app.exec_()
