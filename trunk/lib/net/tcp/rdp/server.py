#!/usr/bin/env python26
#
# PURPOSE: To import the standard includes and setup the package
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

import bisect
import collections
import sys
from PyQt4 import QtGui, QtNetwork, QtCore
from PyQt4.QtCore import Qt

PORT = 9407
SIZEOF_UINT16 = 2
MAX_BOOKINGS_PER_DAY = 5
STREAM_VERSION = QtCore.QDataStream.Qt_4_2

# Key = date, value = list of room IDs
Bookings = collections.defaultdict(list)


def printBookings():
    for key in sorted(Bookings):
        print key, Bookings[key]
    print


class Thread(QtCore.QThread):

    lock = QtCore.QReadWriteLock()

    def __init__(self, socketId, parent):
        super(Thread, self).__init__(parent)
        self.socketId = socketId


    def run(self):
        socket = QtNetwork.QTcpSocket()

        if not socket.setSocketDescriptor(self.socketId):
            self.emit(QtCore.SIGNAL("error(int)"), socket.error())
            return

        while socket.state() == QtNetwork.QAbstractSocket.ConnectedState:
            nextBlockSize = 0
            stream = QtCore.QDataStream(socket)
            stream.setVersion(STREAM_VERSION)

            if socket.waitForReadyRead() and socket.bytesAvailable() >= SIZEOF_UINT16:
                nextBlockSize = stream.readUInt16()

            else:
                self.sendError(socket, "Cannot read client request")
                return

            if socket.bytesAvailable() < nextBlockSize:
                if not socket.waitForReadyRead(60000) or socket.bytesAvailable() < nextBlockSize:
                    self.sendError(socket, "Cannot read client data")
                    return

            action = QtCore.QString()
            room = QtCore.QString()
            date = QtCore.QDate()
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
        reply = QtCore.QByteArray()
        stream = QtCore.QDataStream(reply, QtCore.QIODevice.WriteOnly)
        stream.setVersion(STREAM_VERSION)
        stream.writeUInt16(0)
        stream << QtCore.QString("ERROR") << QtCore.QString(msg)
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - SIZEOF_UINT16)
        socket.write(reply)


    def sendReply(self, socket, action, room, date):
        reply = QtCore.QByteArray()
        stream = QtCore.QDataStream(reply, QtCore.QIODevice.WriteOnly)
        stream.setVersion(STREAM_VERSION)
        stream.writeUInt16(0)
        stream << action << room << date
        stream.device().seek(0)
        stream.writeUInt16(reply.size() - SIZEOF_UINT16)
        socket.write(reply)


class TcpServer(QtNetwork.QTcpServer):

    def __init__(self, parent=None):
        super(TcpServer, self).__init__(parent)


    def incomingConnection(self, socketId):
        thread = Thread(socketId, self)
        self.connect(thread, QtCore.SIGNAL("finished()"),
                     thread, QtCore.SLOT("deleteLater()"))
        thread.start()


class BuildingServicesDlg(QtCore.QObject):

    def __init__(self, parent=None):
        super(BuildingServicesDlg, self).__init__(parent)
        self.loadBookings()
        self.tcpServer = TcpServer(self)

        if not self.tcpServer.listen(QtNetwork.QHostAddress("0.0.0.0"), PORT):
            print "Building Services Server: %s" % self.tcpServer.errorString()
            self.close()
            return

    def loadBookings(self):
        # Generate fake data
        import random

        today = QtCore.QDate.currentDate()
        for i in range(10):
            date = today.addDays(random.randint(7, 60))
            for j in range(random.randint(1, MAX_BOOKINGS_PER_DAY)):
                # Rooms are 001..534 excl. 100, 200, ..., 500
                floor = random.randint(0, 5)
                room = random.randint(1, 34)
                bookings = Bookings[date.toPyDate()]
                if len(bookings) >= MAX_BOOKINGS_PER_DAY:
                    continue
                bisect.insort(bookings, u"%1d%02d" % (floor, room))
        printBookings()

import signal
signal.signal(signal.SIGINT, signal.SIG_DFL)
app = QtCore.QCoreApplication(sys.argv)
server = BuildingServicesDlg()
app.exec_()

