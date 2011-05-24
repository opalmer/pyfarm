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


import sys

from PyQt4 import QtGui, QtNetwork, QtCore
from PyQt4.QtCore import Qt

MAC = "qt_mac_set_native_menubar" in dir()

PORT = 9407
SIZEOF_UINT16 = 2
STREAM_VERSION = QtCore.QDataStream.Qt_4_2

class Client(QtGui.QWidget):

    def __init__(self, parent=None):
        super(Client, self).__init__(parent)

        self.socket = QtNetwork.QTcpSocket()
        self.nextBlockSize = 0
        self.request = None

        roomLabel = QtGui.QLabel("&Room")
        self.roomEdit = QtGui.QLineEdit()
        roomLabel.setBuddy(self.roomEdit)
        regex = QtCore.QRegExp(r"[0-9](?:0[1-9]|[12][0-9]|3[0-4])")
        self.roomEdit.setValidator(QtGui.QRegExpValidator(regex, self))
        self.roomEdit.setAlignment(Qt.AlignRight|Qt.AlignVCenter)
        dateLabel = QtGui.QLabel("&Date")
        self.dateEdit = QtGui.QDateEdit()
        dateLabel.setBuddy(self.dateEdit)
        self.dateEdit.setAlignment(Qt.AlignRight|Qt.AlignVCenter)
        self.dateEdit.setDate(QtCore.QDate.currentDate().addDays(1))
        self.dateEdit.setDisplayFormat("yyyy-MM-dd")
        responseLabel = QtGui.QLabel("Response")
        self.responseLabel = QtGui.QLabel()
        self.responseLabel.setFrameStyle(QtGui.QFrame.StyledPanel|
                                         QtGui.QFrame.Sunken)

        self.bookButton = QtGui.QPushButton("&Book")
        self.bookButton.setEnabled(False)
        self.unBookButton = QtGui.QPushButton("&Unbook")
        self.unBookButton.setEnabled(False)
        quitButton = QtGui.QPushButton("&Quit")

        if not MAC:
            self.bookButton.setFocusPolicy(Qt.NoFocus)
            self.unBookButton.setFocusPolicy(Qt.NoFocus)

        buttonLayout = QtGui.QHBoxLayout()
        buttonLayout.addWidget(self.bookButton)
        buttonLayout.addWidget(self.unBookButton)
        buttonLayout.addStretch()
        buttonLayout.addWidget(quitButton)
        layout = QtGui.QGridLayout()
        layout.addWidget(roomLabel, 0, 0)
        layout.addWidget(self.roomEdit, 0, 1)
        layout.addWidget(dateLabel, 0, 2)
        layout.addWidget(self.dateEdit, 0, 3)
        layout.addWidget(responseLabel, 1, 0)
        layout.addWidget(self.responseLabel, 1, 1, 1, 3)
        layout.addLayout(buttonLayout, 2, 1, 1, 4)
        self.setLayout(layout)

        self.connect(self.socket, QtCore.SIGNAL("connected()"),
                     self.sendRequest)
        self.connect(self.socket, QtCore.SIGNAL("readyRead()"),
                     self.readResponse)
        self.connect(self.socket, QtCore.SIGNAL("disconnected()"),
                     self.serverHasStopped)
        self.connect(self.socket,
                     QtCore.SIGNAL("error(QAbstractSocket::SocketError)"),
                     self.serverHasError)
        self.connect(self.roomEdit, QtCore.SIGNAL("textEdited(QString)"),
                     self.updateUi)
        self.connect(self.dateEdit, QtCore.SIGNAL("dateChanged(QDate)"),
                     self.updateUi)
        self.connect(self.bookButton, QtCore.SIGNAL("clicked()"),
                     self.book)
        self.connect(self.unBookButton, QtCore.SIGNAL("clicked()"),
                     self.unBook)
        self.connect(quitButton, QtCore.SIGNAL("clicked()"), self.close)

        self.setWindowTitle("Building Services")


    def updateUi(self):
        enabled = False
        if not self.roomEdit.text().isEmpty() and \
           self.dateEdit.date() > QtCore.QDate.currentDate():
            enabled = True
        if self.request is not None:
            enabled = False
        self.bookButton.setEnabled(enabled)
        self.unBookButton.setEnabled(enabled)


    def closeEvent(self, event):
        self.socket.close()
        event.accept()


    def book(self):
        self.issueRequest(QtCore.QString("BOOK"), self.roomEdit.text(),
                          self.dateEdit.date())


    def unBook(self):
        self.issueRequest(QtCore.QString("UNBOOK"), self.roomEdit.text(),
                          self.dateEdit.date())


    def issueRequest(self, action, room, date):
        self.request = QtCore.QByteArray()
        stream = QtCore.QDataStream(self.request, QtCore.QIODevice.WriteOnly)
        stream.setVersion(STREAM_VERSION)
        stream.writeUInt16(0)
        stream << action << room << date
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)
        self.updateUi()
        if self.socket.isOpen():
            self.socket.close()
        self.responseLabel.setText("Connecting to server...")
        self.socket.connectToHost("localhost", PORT)


    def sendRequest(self):
        self.responseLabel.setText("Sending request...")
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None


    def readResponse(self):
        stream = QtCore.QDataStream(self.socket)
        stream.setVersion(STREAM_VERSION)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < SIZEOF_UINT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break
            action = QtCore.QString()
            room = QtCore.QString()
            date = QtCore.QDate()
            stream >> action >> room
            if action != "ERROR":
                stream >> date
            if action == "ERROR":
                msg = QtCore.QString("Error: %1").arg(room)
            elif action == "BOOK":
                msg = QtCore.QString("Booked room %1 for %2").arg(room) \
                              .arg(date.toString(Qt.ISODate))
            elif action == "UNBOOK":
                msg = QtCore.QString("Unbooked room %1 for %2").arg(room) \
                              .arg(date.toString(Qt.ISODate))

            self.responseLabel.setText(msg)
            self.updateUi()
            self.nextBlockSize = 0


    def serverHasStopped(self):
        self.responseLabel.setText(
                "Error: Connection closed by server")
        self.socket.close()


    def serverHasError(self, error):
        self.responseLabel.setText(QString("Error: %1") \
                .arg(self.socket.errorString()))
        self.socket.close()


app = QtGui.QApplication(sys.argv)
form = Client()
form.show()
app.exec_()

