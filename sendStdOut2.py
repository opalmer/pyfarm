#!/usr/bin/python

import os
import sys
from PyQt4.QtGui import *
from PyQt4.QtCore import *
from lib.ui.QProcessTesting import Ui_QProcessTest
from lib.Network import *


class QProcessTest2(QObject):
    def __init__(self, parent=None):
        super(QProcessTest2, self).__init__(parent)
#        self.setWindowFlags(Qt.WindowStaysOnTopHint)
#
#        font = self.font()
#        font.setPointSize(24)
#        self.setFont(font)
#        self.setWindowTitle("Building Services Server")
#        self.connect(self, SIGNAL("clicked()"), self.killProcess)

        # setup default arguments
        self.command = QString('Render')
        self.arguments = QStringList()
        self.arguments.append('-r')
        self.arguments.append('mr')
        self.arguments.append('-v')
        self.arguments.append('5')
        self.arguments.append('-s')
        self.arguments.append('1')
        self.arguments.append('-e')
        self.arguments.append('3')
        self.arguments.append('/farm/projects/TECH420/P1_LightChange/maya/scenes/envOcc.mb')

        # job info
        self.jobid = '1A32B'
        self.frame = '23'

        # setup QTcpSocket
        self.socket = TCPStdOutClient("0.0.0.0")
        self.connect(self.socket, SIGNAL("serverDied"), self.killProcess)

        # setup QProcess
        self.process = QProcess(self)
        self.connect(self.process, SIGNAL("started()"), self.processRunning)
        self.connect(self.process, SIGNAL("finished(int)"), self.processFinished)
        self.connect(self.process, SIGNAL("readyReadStandardOutput()"), self.readStandardOutput)
        self.connect(self.process, SIGNAL("readyReadStandardError()"), self.readErrorOutput)

    def killProcess(self):
        self.process.kill()
        sys.exit('Terminated - Server died')

    def readStandardOutput(self):
        '''Read the standard output of a process'''
        line = QString(self.process.readAllStandardOutput())
        self.socket.pack(self.jobid, self.frame, line)
        self.socket.abort()
        #self.socket.disconnectFromHost()

    def readErrorOutput(self):
        '''Read the error output of a process'''
        line = QString(self.process.readAllStandardError())
        self.socket.pack(self.jobid, self.frame, line)
#        self.socket.close()

    def processFinished(self, exitStatus):
        '''Run when self.process emits finished(int), exit code captured'''
        #self.socket.pack(self.jobid, self.frame, "Process Finished")
        #self.socket.pack(self.jobid, self.frame, exitStatus)
        self.socket.close()

    def processRunning(self):
        '''Called when self.process emits started()'''
        self.socket.pack(self.jobid, self.frame, "Process Running")

        if os.name == 'nt':
            self.socket.pack(self.jobid, self.frame, "PID: Not avaliable in windows")
        else:
            self.socket.pack(self.jobid, self.frame, "PID: %s" % self.process.pid())

    def startCommand(self):
        '''Start the command here'''
        #self.socket.pack(self.jobid, self.frame, "Starting Process")
        self.process.start(self.command, self.arguments)

    def stopCommand(self):
        '''Stop running command'''
        self.socket.pack(self.jobid, self.frame, "Stopping Process")
        self.process.close()


app = QApplication(sys.argv)
process = QProcessTest2()
process.startCommand()
app.exec_()
