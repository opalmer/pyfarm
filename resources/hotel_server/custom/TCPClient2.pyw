#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Jan 12 2009
PURPOSE: TCP client used to send information to the server and react to
signals sent from the server
'''

import sys
from PyQt4.QtCore import *
from PyQt4.QtGui import *
from PyQt4.QtNetwork import *
from lib.ui.RC1 import Ui_RC1

PORT = 9407
SIZEOF_UINT16 = 2

class RC1(QMainWindow):
    def __init__(self):
        super(RC1, self).__init__()

        # setup UI
        self.ui = Ui_RC1()
        self.ui.setupUi(self)

        # setup ui vars
        #more to come soon

        # setup socket vars
        self.socket = QTcpSocket()
        self.nextBlockSize = 0
        self.request = None

        # make signal connections
        ## ui signals
        self.connect(self.ui.render, SIGNAL("pressed()"), self._gatherInfo)

        ## socket signals
        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

    def _gatherInfo(self):
        self.job = self.ui.inputJobName.text()
        self.sFrame = self.ui.inputStartFrame.text()
        self.eFrame = self.ui.inputEndFrame.text()
        self.issueRequest(QString("RENDER"), self.job, self.sFrame)

    def _updateStatus(self, section, msg, color='black'):
        '''
        Update the ui's status window

        VARS:
            section (string)-- The section to report from (ex. NETWORK)
            msg (string) - The message to post
            color (string) - The color name or hex value to set the section
        '''
        self.ui.status.append('<font color=%s><b>%s</b></font> - %s' % (color, section, msg))

    def issueRequest(self, action, job, frame):
        '''Pack the data and ready it to be sent'''
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << job << frame
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)
        if self.socket.isOpen():
            self.socket.close()
        self._updateStatus('TCPClient (gui)', 'Packing request', 'green')

        # once the socket emits connected() self.sendRequest is called
        self.socket.connectToHost("localhost", PORT)

    def sendRequest(self):
        '''Send the requested data to the remote server'''
        self._updateStatus('TCPClient (gui)', 'Sending request', 'green')
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        '''Read the response from the server'''
        self._updateStatus('TCPServer', 'Successful connection', 'green')
        self._updateStatus('TCPClient (gui)', 'Reading response', 'green')
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
            job = QString()
            frame = QString()

            stream >> action >> job >> frame
            if action == "ERROR":
                msg = QString("Error: %1").arg(command)
            elif action == "RENDER":
                msg = QString("Rendering frame %2 of job %1").arg(job).arg(frame)
                self._updateStatus('TCPServer', msg, 'green')
            self.nextBlockSize = 0

    def serverHasStopped(self):
        '''Run upon server shutdown'''
        self._updateStatus('TCPClient (gui)', '<font color=red><b>Server Thread Killed</b></font>','green')
        self.socket.close()

    def serverHasError(self, error):
        '''Gather errors then close the connection'''
        self._updateStatus('TCPClient (gui)', QString("<font color='red'><b>Error: %1</b></font>").arg(self.socket.errorString()), 'green')
        self.socket.close()

if __name__ == "__main__":
    app = QApplication(sys.argv)
    ui = RC1()
    ui.show()
    app.exec_()
