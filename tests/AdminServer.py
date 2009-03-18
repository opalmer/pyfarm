#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: March 16 2009
PURPOSE: To design and test the admin server in a clean working environment

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
from time import sleep
from PyQt4.QtCore import *
from PyQt4.QtNetwork import *

ADMIN_PORT = 9407
SIZEOF_UINT16 = 2

class AdminServerThread(QThread):
    '''Admin server thread spawned by AdminServer'''
    def __init__(self, socketId, parent):
        super(AdminServerThread, self).__init__(parent)
        self.socketId = socketId

    def run(self):
        '''
        The main function of the thread as called by
        AdminServer @ AdminServerThread.start()
        '''
        socket = QTcpSocket()

        if not socket.setSocketDescriptor(self.socketId):
            self.emit(SIGNAL("error(int)"), socket.error())
            return

        # while we are connected
        while socket.state() == QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QDataStream(socket) # create a data stream
            stream.setVersion(QDataStream.Qt_4_2)

            while True:
                # wait for the stream to be ready to read
                socket.waitForReadyRead(-1)

                # load next block size with the total size of the stream
                if socket.bytesAvailable() >= SIZEOF_UINT16:
                    nextBlockSize = stream.readUInt16()
                    break

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                        break

            # prepare vars for input stream
            action = QString()
            options = QString()
            stream >> action

            # if the action is a preset
            if action in ("SHUTDOWN", "RESTART", "HALT"):
                stream >> options # unpack the stream
                if action == "SHUTDOWN":
                    #self.quit("SHUTDOWN")
                    self.emit(SIGNAL("SHUTDOWN"))
                elif action == "RESTART":
                    self.emit(SIGNAL("RESTART"))
                elif action == "HALT":
                    self.emit(SIGNAL("HALT"))

                # final send a back the original host
                self.sendReply(socket, action, options)

            # unless the user requested an action that does
            #  not exist
            else:
                    self.sendError(socket, "%s is not a valid option" % action)
            socket.waitForDisconnected()

    def sendError(self, socket, msg):
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << QString("ERROR") << QString(msg)
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - SIZEOF_UINT16)
        socket.write(reply)

    def sendReply(self, socket, action, options):
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << options
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - SIZEOF_UINT16)
        socket.write(reply)


class AdminServer(QTcpServer):
    '''
    Primary Admin Server
    Takes incomingConnection and starts a thread
    to handle the connection, keeps the connection from blocking.
    '''
    def __init__(self, parent=None):
        super(AdminServer, self).__init__(parent)

    def incomingConnection(self, socketId):
        self.serverThread = AdminServerThread(socketId, self)
        self.connect(self.serverThread, SIGNAL("finished()"), self.serverThread, SLOT("deleteLater()"))
        self.connect(self.serverThread, SIGNAL("SHUTDOWN"), self.shutdown)
        self.connect(self.serverThread, SIGNAL("RESTART"), self.restart)
        self.connect(self.serverThread, SIGNAL("HALT"), self.halt)
        self.serverThread.start()

    def shutdown(self):
        '''
        After receiving the shutdown signal from the thread,
        emit SHUTDOWN to the parent.
        '''
        self.serverThread.terminate()
        self.serverThread.wait()
        self.emit(SIGNAL("SHUTDOWN"))

    def restart(self):
        '''
        After receiving the restart signal from the thread,
        emit RESTART to the parent.
        '''
        self.emit(SIGNAL("RESTART"))

    def halt(self):
        '''
        After receiving the restart signal from the thread,
        emit RESTART to the parent.
        '''
        self.emit(SIGNAL("HALT"))


class Main(QObject):
    '''
    Main function that spawns admin servers and other functions
    and waits for their shutdown/restart/halt signals
    '''
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)

        self.admin = AdminServer(self)
        self.connect(self.admin, SIGNAL("SHUTDOWN"), self.shutdown)
        self.connect(self.admin, SIGNAL("RESTART"), self.restart)
        self.connect(self.admin, SIGNAL("HALT"), self.halt)

        if not self.admin.listen(QHostAddress("0.0.0.0"), ADMIN_PORT):
            print "PyFarm :: Main.AdminServer :: Could not start the server"
            return

    def shutdown(self):
        '''If the admin servers calls for it, shutdown the client'''
        self.admin.close()
        sys.exit("PyFarm :: Network.AdminMain :: Closed by Admin Server")

    def restart(self):
        '''If the admin servers calls for it, restart the client'''
        pass

    def halt(self):
        '''If the admin servers calls for it, half the client'''
        pass


app = QCoreApplication(sys.argv)
Main()
app.exec_()
