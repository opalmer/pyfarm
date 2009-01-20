'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 16 2008
PURPOSE: Network modules used to help facilitate network communication
'''
# python libs
import sys
import time
import socket
# pyfarm libs
from Info import System
from FarmLog import FarmLog
# Qt libs
from PyQt4.QtCore import *
from PyQt4.QtNetwork import *
from PyQt4.QtGui import * # < - tmp

## SETUP SOME STANDARD VARS FOR NETWORK USE
SIZEOF_UINT16 = 2

## SETUP PORTS FOR NETWORK (Range: 9630-9699)
BROADCAST_PORT = 9630
TCP_PORT = 9631
UDP_PORT = 9632

class BroadcastServer(QThread):
    '''
    Threaded client to recieve a multicast packet and inform the server of ip and port

    REQUIRES:
        Python:
            socket

        PyQt:
            QThread

        PyFarm:
            FarmLog

    INPUT:
        parent (str) - the thread to parent to. Example: a = MulticastServer(self)
        port (int) - incoming number, defaults to 51423
        host (str) - host to bind to UDP, defaults to ALL
        timeout (int) - timeout the operation after this amount of time

      NOTE: Get hostname with socket.hostname() <- might be required by QTcpServer
    '''
    def __init__(self,  parent, host='', timeout=5):
        super(MulticastServer,  self).__init__(parent)
        self.log = FarmLog("Network.MulticastServer()")
        self.log.setLevel('debug')
        self.port = BROADCAST_PORT
        self.host = host
        self.dest = ('<broadcast>', self.port)
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        self.sock.settimeout(timeout)
        self.done = False

    def run(self):
        '''Send the broadcast packet accross the network'''
        try:
            nodes = []
            # get ready to broadcast
            self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
            self.sock.sendto("I'm a server, my name is %s" % System().name(), self.dest)
            self.log.debug("Looking for nodes; press Ctrl-C to stop.")

            while True:
                (message, address) = self.sock.recvfrom(2048)
                self.log.debug("Found Node: %s:%s" % (address[0], address[1]))
                self.emit(SIGNAL('gotNode'), '%s:%s' % (address[0], str(address[1])))

        except (socket.timeout,  socket.error):
            pass

        finally:
            self.done = True
            self.emit(SIGNAL('DONE'), self.done)


class BroadcastClient(QThread):
    '''
    Threaded client to recieve a multicast packet and inform the server of ip and port

    REQUIRES:
        Python:
            socket

        PyQt:
            QThread

        PyFarm:
            FarmLog

    INPUT:
        port (int) - incoming number, defaults to 51423
        host (str) - host to bind to UDP, defaults to ALL
        parent - None.  By parenting to none it the client runs on its own, not bound to original thread.

    OUTPUT:
        address (None) - Example: ['10.56.1.5', 51423]

    NOTE: Get hostname with socket.hostname() <- might be required by QTcpServer
    '''
    def __init__(self, host='', parent=None):
        super(MulticastClient,  self).__init__(parent)
        self.log = FarmLog("Network.MulticastClient()")
        self.log.setLevel('debug')
        self.port = BROADCAST_PORT
        self.host = host
        self.sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

    def run(self):
        '''Receieve the broadcast packet and reply to the host'''
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        self.sock.bind((self.host, self.port)) #setup a socket to receieve a connection
        self.log.debug("Looking for server; press Ctrl-C to stop.")

        while True:
            try:
                # this loop is run ever time a connection comes in
                message, address = self.sock.recvfrom(8192) # get the connection info and message
                self.log.debug("Found Server: %s:%s" % (address[0], address[1]))

                # Now, reply back to the server with our address and message
                self.sock.sendto("I'm a client, my name is %s" % System().name(), address)

            except (KeyboardInterrupt, SystemExit):
                sys.exit(self.log.critical('PROGRAM TERMINATED'))


class UDPServer(QThread):
    '''Simple server to send udp communications'''
    pass


class UDPClient(QThread):
    '''
    Simple client to receieve udp communications

    REQUIRES:
        Python:
            socket

        PyQt:
            QThread

        PyFarm:
            FarmLog

        INPUT:
            port (int) - incoming number, defaults to 51423
            host (str) - host to bind to UDP, defaults to ALL
            parent - None.  By parenting to none it the client runs on its own, not bound to original thread.
    '''
    def __init__(self, host='', parent=None):
        super(UDPClient, self).__init__(parent)
        self.log = FarmLog("Network.UDPClient")
        self.log.setLevel('debug')
        self.port = UDP_PORT
        self.host = host

    def run(self):
        '''Receieve the broadcast packet and reply to the host'''


class TCPServerThread(QThread):
    '''Called by TCP Server upon new connection'''
    lock = QReadWriteLock()

    def __init__(self, socketid, parent):
        super(TCPServerThread, self).__init__(parent)
        self.socketid = socketid

    def run(self):
        '''Run this one the thread is created'''
        print "TCPServerThread() - DEBUG - Started thread"

        socket =  QTcpSocket()
        if not socket.setSocketDescriptor(self.socketid):
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
                    TCPServerThread.lock.lockForRead()
                finally:
                    TCPServerThread.lock.unlock()

                uroom = unicode(room)
class TCPServer(QTcpServer):
    '''Threaded CP Server used to handle incoming requests'''
    def __init__(self, parent=None):
        super(TCPServer, self).__init__(parent)

    def incomingConnection(self, socketid):
        '''If a new connection is found, start a thread for it'''
        thread = TCPServerThread(socketid, self)
        print "TCPServer() - DEBUG - Got incoming connection at %s" % socketid
        self.connect(thread, SIGNAL("finished()"), thread, SLOT("deleteLater()"))
        thread.start()

class TCPClient(QWidget):
    '''TCP Client used to send information to threaded TCP Server'''
    def __init__(self, parent=None):
        super(TCPClient, self).__init__(parent)

        self.socket = QTcpSocket()
        self.nextBlockSize = 0
        self.request = None

        roomLabel = QLabel("&Room")
        self.roomEdit = QLineEdit()
        roomLabel.setBuddy(self.roomEdit)
        regex = QRegExp(r"[0-9](?:0[1-9]|[12][0-9]|3[0-4])")
        self.roomEdit.setValidator(QRegExpValidator(regex, self))
        self.roomEdit.setAlignment(Qt.AlignRight|Qt.AlignVCenter)
        dateLabel = QLabel("&Date")
        self.dateEdit = QDateEdit()
        dateLabel.setBuddy(self.dateEdit)
        self.dateEdit.setAlignment(Qt.AlignRight|Qt.AlignVCenter)
        self.dateEdit.setDate(QDate.currentDate().addDays(1))
        self.dateEdit.setDisplayFormat("yyyy-MM-dd")
        responseLabel = QLabel("Response")
        self.responseLabel = QLabel()
        self.responseLabel.setFrameStyle(QFrame.StyledPanel|QFrame.Sunken)

        self.bookButton = QPushButton("&Book")
        quitButton = QPushButton("&Quit")
        MAC = "qt_mac_set_native_menubar" in dir()
        if not MAC:
            self.bookButton.setFocusPolicy(Qt.NoFocus)

        buttonLayout = QHBoxLayout()
        buttonLayout.addWidget(self.bookButton)
        buttonLayout.addStretch()
        buttonLayout.addWidget(quitButton)
        layout = QGridLayout()
        layout.addWidget(roomLabel, 0, 0)
        layout.addWidget(self.roomEdit, 0, 1)
        layout.addWidget(dateLabel, 0, 2)
        layout.addWidget(self.dateEdit, 0, 3)
        layout.addWidget(responseLabel, 1, 0)
        layout.addWidget(self.responseLabel, 1, 1, 1, 3)
        layout.addLayout(buttonLayout, 2, 1, 1, 4)
        self.setLayout(layout)

        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)
        self.connect(self.roomEdit, SIGNAL("textEdited(QString)"), self.updateUi)
        self.connect(self.dateEdit, SIGNAL("dateChanged(QDate)"), self.updateUi)
        self.connect(self.bookButton, SIGNAL("clicked()"), self.book)
        self.connect(quitButton, SIGNAL("clicked()"), self.close)

        self.setWindowTitle("Building Services")

    def updateUi(self):
        enabled = False
        if not self.roomEdit.text().isEmpty() and self.dateEdit.date() > QDate.currentDate():
            enabled = True
        if self.request is not None:
            enabled = False
        self.bookButton.setEnabled(enabled)

    def closeEvent(self, event):
        self.socket.close()
        event.accept()

    def book(self):
        # example
        # self.issueRequest(action, command, job, frame)
        self.issueRequest(QString("BOOK"), self.roomEdit.text(), self.dateEdit.date())

    def issueRequest(self, action, command, date):
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << command << date
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)
        self.updateUi()
        if self.socket.isOpen():
            self.socket.close()
        self.responseLabel.setText("Connecting to server...")

        # once the socket emits connected() self.sendRequest is called
        self.socket.connectToHost("0.0.0.0", TCP_PORT)

    def sendRequest(self):
        self.responseLabel.setText("Sending request...")
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
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
            command = QString()
            date = QDate()
            stream >> action >> command
            if action != "ERROR":
                stream >> date
            if action == "ERROR":
                msg = QString("Error: %1").arg(command)
            elif action == "BOOK":
                msg = QString("Booked room %1 for %2").arg(command).arg(date.toString(Qt.ISODate))
            elif action == "UNBOOK":
                msg = QString("Unbooked room %1 for %2").arg(command).arg(date.toString(Qt.ISODate))
            self.responseLabel.setText(msg)
            self.updateUi()
            self.nextBlockSize = 0

    def serverHasStopped(self):
        self.responseLabel.setText("Error: Connection closed by server")
        self.socket.close()

    def serverHasError(self, error):
        self.responseLabel.setText(QString("Error: %1").arg(self.socket.errorString()))
        self.socket.close()


class WakeOnLan(object):
    '''Designed to utilize wake on lan to startup a remote machine'''
    def wake( macaddress ):
        '''
        Wake computer with given mac address

        NOTE: address can either include or omit the colons.
        '''
        # if the len of macaddress = 12 do nothing
        if len(macaddress) == 12:
            pass

        # if it does not, add : every third character
        elif len(macaddress) == 12 + 5:
            sep = macaddress[2]
            macaddress = macaddress.replace(sep, '')

        # unless they did something wrong.....
        else:
            raise ValueError('Incorrect MAC address format')

        # Pad the synchronization stream.
        data = ''.join(['FFFFFFFFFFFF', macaddress * 20])
        send_data = ''

        # Split up the hex values and pack.
        for i in range(0, len(data), 2):
            send_data = ''.join([send_data,
                                 struct.pack('B', int(data[i: i + 2], 16))])

        # Broadcast it to the LAN.
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        sock.sendto(send_data, ('<broadcast>', 7))
