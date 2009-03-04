'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Dec 18 2008
PURPOSE: Module used to run processes and create threads

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
from PyQt4.QtCore import *

class RunProcess(QObject):
    '''Class dedicated to running and gathering
    output.

    INPUT:
        command (string) - full command to run
    '''
    def __init__(self, command, arguments, parent=None):
        super(RunProcess, self).__init__(parent)
        self.arguments = QStringList()

        formattedArgs = ''
        sp = arguments.split(' ')

        for arg in sp[0:len(sp)-1]:
            formattedArgs += "%s " % arg

        self.command = QString("%s%s" % (command, formattedArgs))
        self.arguments = QStringList()
        self.arguments.append(QString(sp[len(sp)-1]))


#        sp = arguments.split(' ')[1:len(sp)-1]
#        args = sp[1:len(sp)-1]
#        scene = sp[len(sp)-1]
#        OUT = ''
#
#        for i in args:
#            OUT+= " %s" % i
#
#        self.arguments = QStringList()
#        self.arguments.append(QString(OUT))
#        self.arguments.append(QString(scene))
#        print self.command
#        print list(self.arguments)
        #self.arguments.append(QString(arg))

        self.process = QProcess()

        # setup the connections
        self.connect(self.process, SIGNAL("started()"), self.started)
        self.connect(self.process, SIGNAL("stateChanged(QProcess::ProcessState)"), self.stateChanged)
        self.connect(self.process, SIGNAL("readyReadStandardError()"), self.readStdOut)
        self.connect(self.process, SIGNAL("readyReadStandardOutput()"), self.readStdOut)
        self.connect(self.process, SIGNAL("error(QProcess::ProcessError)"), self.error)
        self.connect(self.process, SIGNAL("finished(int)"), self.processFinished)

    def start(self):
        '''start the program from here'''
        self.process.start(self.command, self.arguments)
        #self.process.waitForFinished(-1)

    def started(self):
        '''Called when process has started'''
        self.emit(SIGNAL("PROCESS_STARTED"))

    def stateChanged(self, state):
        '''Run when the process changes state'''
        self.emit(SIGNAL("STATE_CHANGED"), state)

    def error(self, error):
        self.emit(SIGNAL("PROCESS_ERROR"), error)

    def readStdOut(self):
        '''Read the standard output line'''
        print QString(self.process.readAllStandardError()).trimmed()
        #self.emit(SIGNAL("STDOUT_LINE"), line)

    def readStdErr(self, line):
        '''Read the standard error line'''
        self.emit(SIGNAL("STDERR_LINE"), line)

    def processFinished(self, exitStatus):
        '''Run when self.process emits finished(int), exit code captured'''
        return self.process.close()

    def finished(self):
        pass
