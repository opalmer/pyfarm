'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 11 2009
PURPOSE: Network modules used to hand queue commands

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
from os import getcwd

# From PyQt
from PyQt4.QtCore import QThread, QString, QObject
from PyQt4.QtCore import SIGNAL, QDataStream, QByteArray, QIODevice, SLOT
from PyQt4.QtNetwork import QAbstractSocket, QTcpSocket, QTcpServer

# From PyFarm
from lib.ReadSettings import ParseXmlSettings
settings = ParseXmlSettings('%s/settings.xml' % getcwd(), skipSoftware=False)


class WaitForQueServerThread(QThread):
    '''
    Threaded TCP Server used to handle all incoming
    standard output information.
    '''
    lock = QReadWriteLock()
    def __init__(self, socketid, parent):
        super(WaitForQueServerThread, self).__init__(parent)
        self.socketid = socketid

    def run(self):
        '''Start the server'''
        socket = QTcpSocket()

        if not socket.setSocketDescriptor(self.socketid):
            print "setSocketDescriptor(%s) is NOT 1" % self.socketid
            self.emit(SIGNAL("error(int)"), socket.error())
            return

        # while we are connected, do this
        while socket.state() == QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QDataStream(socket) # the stream is a QDataStream
            stream.setVersion(QDataStream.Qt_4_2) # set the version of the stream
            while True:
                socket.waitForReadyRead(-1)
                if socket.bytesAvailable() >= settings.netGeneral('unit16'):
                    nextBlockSize = stream.readUInt16()
                    job = QString()
                    frame = QString()
                    stdout = QString()
                    host = QString()
                    output = QStringList()
                    stream >> job >> frame >>  host >> stdout
                    for arg in (job, frame, host, stdout):
                        output.append(arg)

                    self.emit(SIGNAL("emitStdOutLine"), output)

            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                       pass

        socket.close()


class WaitForQueServer(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(WaitForQueServer, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        print "PyFarm :: Network.WaitForQueServer :: Incoming Connection!"
        thread = TCPServerStdOutThread(socketid, self)
        self.connect(thread, SIGNAL("emitStdOutLine"), self.emitLine)
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

    def emitLine(self, line):
        self.emit(SIGNAL("emitStdOutLine"), line)
