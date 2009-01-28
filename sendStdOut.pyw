#!/usr/bin/python

import os
import sys
from PyQt4.QtGui import *
from PyQt4.QtCore import *
from lib.ui.QProcessTesting import Ui_QProcessTest
from lib.Network import *

class QProcessTest(QWidget):
    def __init__(self, parent=None):
        super(QProcessTest, self).__init__(parent)
        self.ui = Ui_QProcessTest()
        self.ui.setupUi(self)

        # setup buttons and line edits
        self.connect(self.ui.startButton, SIGNAL("clicked()"), self.startCommand)
        self.connect(self.ui.stopButton, SIGNAL("clicked()"), self.stopCommand)
        self.connect(self.ui.command, SIGNAL("editingFinished()"), self.setCommand)
        self.connect(self.ui.arguments, SIGNAL("editingFinished()"), self.setArguments)

        # setup the output consoles
        self.mainOutLine = 1
        self.errorOutLine = 1
        self.debugOutLine = 1

        # setup QProcess
        self.command = self.ui.command.text()
        self.arguments = QStringList()
        self.process = QProcess(self)
        self.socket = TCPStdOutClient()
        self.connect(self.process, SIGNAL("started()"), self.processRunning)
        self.connect(self.process, SIGNAL("finished(int)"), self.processFinished)
        self.connect(self.process, SIGNAL("readyReadStandardOutput()"), self.readStandardOutput)
        self.connect(self.process, SIGNAL("readyReadStandardError()"), self.readErrorOutput)

    def setCommand(self):
        '''Called by the command line edit, sets self.command'''
        self.command = self.ui.command.text()
        self.debugOut("Command: %s" % self.command)

    def setArguments(self):
        '''Called by the arguments line edit, sets self.arguments'''
        self.arguments = QStringList()
        for arg in self.ui.arguments.text().split(' '):
            self.arguments.append(arg)

        args = []
        for i in self.arguments:
            args.append(str(i))

        self.debugOut("Arguments: %s" % args)

    def readStandardOutput(self):
        '''Read the standard output of a process'''
        line = QString(self.process.readAllStandardOutput())
        self.mainOut(line)
        self.socket.pack('J1', 'F1', line)

    def readErrorOutput(self):
        '''Read the error output of a process'''
        self.errorOut(QString(self.process.readAllStandardError()))

    def processFinished(self, exitStatus):
        '''Run when self.process emits finished(int), exit code captured'''
        self.debugOut("Process Finished")
        self.debugOut("Exit Code: %i" % self.process.exitCode())
        self.ui.startButton.setEnabled(True)
        self.ui.stopButton.setEnabled(False)

    def processRunning(self):
        '''Called when self.process emits started()'''
        self.debugOut("Process Running")
        self.ui.startButton.setEnabled(False)
        self.ui.stopButton.setEnabled(True)

        if os.name == 'nt':
            self.debugOut('PID: <i>Not available on windows</i>')
        else:
            self.debugOut("PID: %s" % self.process.pid())

    def startCommand(self):
        '''Start the command here'''
        self.debugOut("Starting the process")
        self.process.start(self.command, self.arguments)

    def stopCommand(self):
        '''Stop running command'''
        self.debugOut("Stopping the process")
        self.process.close()

    def mainOut(self, message):
        '''Generate an output line for main'''
        self.ui.mainOut.append("<b>%i</b> - %s" % (self.mainOutLine, message))
        self.mainOutLine += 1

    def errorOut(self, message):
        '''Generate an output line for error'''
        self.ui.mainOut.append("<b>%i</b> - %s" % (self.errorOutLine, message))
        self.errorOutLine += 1

    def debugOut(self, message):
        '''Generate an output line for debug'''
        self.ui.generalOut.append("<b>%i</b> - %s" % (self.debugOutLine, message))
        self.debugOutLine += 1


app = QApplication(sys.argv)
ui = QProcessTest()
ui.show()
app.exec_()
