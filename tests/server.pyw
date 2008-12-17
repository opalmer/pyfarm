#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 16 2008
PURPOSE: Used to create a small gui and receieve information
'''

import bisect
import collections
import sys
from PyQt4.QtCore import *
from PyQt4.QtGui import *
from PyQt4.QtNetwork import *

PORT = 9407
SIZEOF_UINT16 = 2
MAX_BOOKINGS_PER_DAY = 5

# Key = date, value = list of room IDs
Bookings = collections.defaultdict(list)

class Thread(QThread):
    lock = QReadWriteLock()
    def __init__(self, socketId, parent):
        super(Thread, self).__init__(parent)
        self.socketId = socketId

    def run(self):
        socket = QTcpSocket()
        if not socket.setSocketDescriptor(self.socketId):
            self.emit(SIGNAL("error(int)"), socket.error())
            return
        while socket.state() == QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QDataStream(socket)
            stream.setVersion(QDataStream.Qt_4_2)
            while True:
                socket.waitForReadyRead(-1)
                if socket.bytesAvailable() >= SIZEOF_UINT16:
                    nextBlockSize = stream.readUInt16()
                    break
            if socket.bytesAvailable() < nextBlockSize:
                while True:
                    socket.waitForReadyRead(-1)
                    if socket.bytesAvailable() >= nextBlockSize:
                        break
            action = QString()
            room = QString()
            date = QDate()
            stream >> action
            if action in ("BOOK", "UNBOOK"):
                stream >> room >> date
                try:
                    Thread.lock.lockForRead()
                    bookings = Bookings.get(date.toPyDate())
                finally:
                    Thread.lock.unlock()
                uroom = unicode(room)
            if action == "BOOK":
                newlist = False
                try:
                    Thread.lock.lockForRead()
                    if bookings is None:
                        newlist = True
                finally:
                    Thread.lock.unlock()
                if newlist:
                    try:
                        Thread.lock.lockForWrite()
                        bookings = Bookings[date.toPyDate()]
                    finally:
                        Thread.lock.unlock()
                error = None
                insert = False
                try:
                    Thread.lock.lockForRead()
                    if len(bookings) < MAX_BOOKINGS_PER_DAY:
                        if uroom in bookings:
                            error = "Cannot accept duplicate booking"
                        else:
                            insert = True
                    else:
                        error = QString("%1 is fully booked").arg(
                                        date.toString(Qt.ISODate))
                finally:
                    Thread.lock.unlock()
                if insert:
                    try:
                        Thread.lock.lockForWrite()
                        bisect.insort(bookings, uroom)
                    finally:
                        Thread.lock.unlock()
                    self.sendReply(socket, action, room, date)
                else:
                    self.sendError(socket, error)
            elif action == "UNBOOK":
                error = None
                remove = False
                try:
                    Thread.lock.lockForRead()
                    if bookings is None or uroom not in bookings:
                        error = "Cannot unbook nonexistent booking"
                    else:
                        remove = True
                finally:
                    Thread.lock.unlock()
                if remove:
                    try:
                        Thread.lock.lockForWrite()
                        bookings.remove(uroom)
                    finally:
                        Thread.lock.unlock()
                    self.sendReply(socket, action, room, date)
                else:
                    self.sendError(socket, error)
            else:
                self.sendError(socket, "Unrecognized request")
            socket.waitForDisconnected()
            try:
                Thread.lock.lockForRead()
                printBookings()
            finally:
                Thread.lock.unlock()


    def sendError(self, socket, msg):
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << QString("ERROR") << QString(msg)
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - SIZEOF_UINT16)
        socket.write(reply)


    def sendReply(self, socket, action, room, date):
        reply = QByteArray()
        stream = QDataStream(reply, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << room << date
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - SIZEOF_UINT16)
        socket.write(reply)


class TcpServer(QTcpServer):

    def __init__(self, parent=None):
        super(TcpServer, self).__init__(parent)


    def incomingConnection(self, socketId):
        thread = Thread(socketId, self)
        self.connect(thread, SIGNAL("finished()"),
                     thread, SLOT("deleteLater()"))
        thread.start()


class ServerGUI(QPushButton):

    def __init__(self, parent=None):
        super(ServerGUI, self).__init__(
                "&Close Server", parent)
        self.setWindowFlags(Qt.WindowStaysOnTopHint)
        self.tcpServer = TcpServer(self)
        if not self.tcpServer.listen(QHostAddress("0.0.0.0"), PORT):
            QMessageBox.critical(self, "Building Services Server",
                    QString("Failed to start server: %1") \
                    .arg(self.tcpServer.errorString()))
            self.close()
            return

        self.connect(self, SIGNAL("clicked()"), self.close)
        font = self.font()
        font.setPointSize(24)
        self.setFont(font)
        self.setWindowTitle("PyFarm Server Prototype 1")

app = QApplication(sys.argv)
servergui = ServerGUI()
servergui.show()
servergui.move(0, 0)
app.exec_()