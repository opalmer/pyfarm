#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Jan 12 2009
PURPOSE: TCP client used to send information to the server and react to
signals sent from the server

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
# From Python
import sys
import os.path
# From PyQt
from PyQt4.QtCore import *
from PyQt4.QtGui import *
from PyQt4.QtNetwork import *
# From PyFarm
## ui components
from lib.ui.RC1 import Ui_RC1
from lib.ui.CustomWidgets import *
## general libs
from lib.Que import *
from lib.Network import *
import lib.ReadSettings as Settings

# setup the required ports (adjust these settings via settings.cfg)
CFG = os.getcwd()+'/settings.cfg'
QUE_PORT = Settings.Network(CFG).QuePort()
BROADCAST_PORT = Settings.Network(CFG).BroadcastPort()
STDOUT_PORT = Settings.Network(CFG).StdOutPort()
STDERR_PORT = Settings.Network(CFG).StdErrPort()
SIZEOF_UINT16 = Settings.Network(CFG).Unit16Size()
SERVE_FROM = Settings.Network(CFG).MasterAddress()

class WorkerThread(QThread):
    '''Used thread out TCP servers for worker threads'''
    def __init__(self, host, port, parent=None):
        super(WorkerThread, self).__init__(parent)
        self.host = host
        self.port = port
        self.client = SendCommand(self.host, self.port)
        self.connect(self.client, SIGNAL("WORK_COMPLETE"), self.workComplete)
        self.connect(self.client, SIGNAL("SENDING_WORK"), self.workSent)

    def run(self):
        '''Start the thread and send the first command'''
        if QUE.size() == 0:
            self.terminate()
        else:
            self.client.issueRequest(QUE.get())

    def workComplete(self, client):
        self.emit(SIGNAL("WORK_COMPLETE"), client)
        if QUE.size() > 0:
            self.client.issueRequest(QUE.get())
        else:
            self.client.issueRequest('TERMINATE_SELF')
            self.emit(SIGNAL("QUE_EMPTY"), self.host)
            self.terminate()

    def workSent(self, work):
        self.emit(SIGNAL("SENDING_WORK"), work)


class RC1(QMainWindow):
    def __init__(self):
        super(RC1, self).__init__()

        # setup UI
        self.ui = Ui_RC1()
        self.ui.setupUi(self)

        # add external libs
        self.netTableLib = NetworkTable()

        # inform the user of their settings
        self.updateStatus('SETTINGS', 'Got settings from ./settings.cfg', 'purple')
        self.updateStatus('SETTINGS', 'Server IP: %s' % SERVE_FROM, 'purple')
        self.updateStatus('SETTINGS', 'Broadcast Port: %s' % BROADCAST_PORT, 'purple')
        self.updateStatus('SETTINGS', 'StdOut Port: %s' % STDOUT_PORT, 'purple')
        self.updateStatus('SETTINGS', 'StdErr Port: %s' % STDERR_PORT, 'purple')
        self.updateStatus('SETTINGS', 'Que Port: %s' % QUE_PORT, 'purple')

        # setup ui vars
        self.hosts = []
        self.foundHosts = 0
        self.ui.networkTable.setAlternatingRowColors(True)
        self.software = self.ui.softwarePackages
        self.netTable = self.ui.networkTable
        self.netTable.horizontalHeader().setStretchLastSection(True)
        self.message = QString()
        self.scene = ''
        self.que = QUE
        self.ui.cancelRender.setEnabled(False)

        # make signal connections
        ## ui signals
        self.connect(self.ui.render, SIGNAL("pressed()"), self.initJob)
        #self.connect(self.ui.cancelRender, SIGNAL("pressed()"), self.killRender)
        self.connect(self.ui.findHosts, SIGNAL("pressed()"), self._findHosts)
        self.connect(self.ui.browseForScene, SIGNAL("pressed()"), self._browseForScene)
        self.connect(self.ui.browseOutputDir, SIGNAL("pressed()"), self._browseForOutputDir)
        self.connect(self.ui.queryQue, SIGNAL("pressed()"), self.queryQue)
        self.connect(self.ui.emptyQue, SIGNAL("pressed()"), self.emptyQue)
        self.connect(self.ui.browseForLogDir, SIGNAL("pressed()"), self._browseForLogDir)
        self.connect(self.ui.enque, SIGNAL("pressed()"), self._gatherInfo)

        ## network section signals
        self.connect(self.ui.disableHost, SIGNAL("pressed()"), self._disableHosts)
        self.connect(self.ui.addHost, SIGNAL("pressed()"), self._customHostDialog)
        self.connect(self.ui.removeHost, SIGNAL("pressed()"), self._removeSelectedHost)

        #############
        # TMP setup for testing
        #############
#        self.ui.inputScene.setText('/farm/projects/PyFarm/trunk/RC1/tests/maya/scenes/occlusion_test.mb')
#        self.ui.inputOutputDir.setText('/farm/tmp/images/')
#        self.ui.inputLogDir.setText('/farm/tmp/logs/')
#        self.ui.inputJobName.setText('rayTest')

    def _browseForOutputDir(self):
        '''Get the output directory'''
        getOutputDir = QFileDialog.getExistingDirectory(\
            None,
            QString(),
            QString(),
            QFileDialog.Options(QFileDialog.ShowDirsOnly))

        if not getOutputDir.isEmpty():
            self.ui.inputOutputDir.setText(getOutputDir)

    def _browseForLogDir(self):
        '''Get the output directory'''
        getLogDir = QFileDialog.getExistingDirectory(\
            None,
            QString(),
            QString(),
            QFileDialog.Options(QFileDialog.ShowDirsOnly))

        if not getLogDir.isEmpty():
            self.ui.inputLogDir.setText(getLogDir)

    def _browseForScene(self):
        '''Browse for a scene to render'''
        getScene = QFileDialog.getOpenFileName(\
            None,
            self.trUtf8("Select File"),
            QString(),
            self.trUtf8("All Files(*.*);;Maya (*.mb *.ma);;Houdini (*.hip *.ifd);;Shake(*.shk)"),
            None,
            QFileDialog.Options(QFileDialog.DontResolveSymlinks))
        if not getScene.isEmpty():
            self.ui.inputScene.setText(getScene)

    def queryQue(self):
        '''Query the current que'''
        self.updateStatus('INFO', 'Number of items in que: %i' % QUE.size())

    def emptyQue(self):
        '''Remote all items from the que'''
        QUE.emptyQue()
        self.updateStatus('INFO', 'Que is now empty!')

    def _removeSelectedHost(self):
        '''Remove the currently selected host'''
        lineHost = self._getHostSelection()
        self.hosts.remove(lineHost[1])
        self.netTable.removeRow(lineHost[0])

    def _getHostSelection(self):
        '''
        Get the current host selection

        OUTPUT:
            list2 - [rowNum, [hostname, ipaddress, status]]
        '''
        output = []
        tmp = []
        output.append(list(self.netTable.selectedIndexes())[0].row())
        for i in list(self.netTable.selectedItems()):
            tmp.append(str(i.text()))

        output.append(tmp)

        return output

    def _customHostDialog(self):
        ''''Open a dialog to add a custom host'''
        self.customHostDialog = AddHostDialog()
        self.connect(self.customHostDialog.buttons, SIGNAL("accepted()"), self._addCustomHost)
        self.customHostDialog.exec_()

    def _addCustomHost(self):
        '''Add the custom host to self.hosts and the gui, close the dialog'''
        host = self.customHostDialog.inputHost.text()
        self.addHost(str(host))
        self.customHostDialog.close()

    def addHost(self, host, warnHostExists=True):
        '''
        Add the given host to the table

        INPUT:
            host (string) - host to add
            warnHostExists (bool) - if false, do not popup info about
            hosts that have already been added
        '''
        # check to make sure the host is valid
        if ResolveHost(host) == 'BAD_HOST':
            self.criticalMessage("Bad host or IP", "Sorry %s could not be resolved, please check your entry and try again." % host)
        else:
            # prepare the information
            self.currentHost = []
            self.hostStatusMenu = HostStatus(self)
            self.currentHost.append(ResolveHost(host)[0])
            self.currentHost.append(ResolveHost(host)[1])
            self.currentHost.append('Online')

            # if the current host has not been added
            if self.currentHost not in self.hosts:
                self.hosts.append(self.currentHost)
                self.addHostToTable(self.currentHost)
                self.foundHosts += 1
            else:
                if warnHostExists:
                    msg = QMessageBox()
                    msg.information(None, "Host Already Added", unicode("%s has already been added to the list." % host))
                else:
                    pass

    def addHostFromBroadcast(self, host):
        '''Add a host generated from the broadcast packet'''
        self.addHost(host, False)

    def addHostToTable(self, resolvedHost):
        '''Add the given host to the table'''
        y = 0
        x = self.ui.networkTable.rowCount()
        self.ui.networkTable.insertRow(self.ui.networkTable.rowCount())

        for attribute in resolvedHost:
            item = QTableWidgetItem(attribute)
            self.ui.networkTable.setItem(x, y, item)
            y += 1

#        a = QTableWidgetItem()
#        b = HostStatusMenu(self)
#        a.setData(0, QVariant(b.resize(300, 300)))
#        b.show()
#        self.ui.networkTable.setItem(x, 2, a)

        #self.netTable.resizeColumnsToContents()

    def _disableHosts(self):
        row = self._getHostSelection()[0]
        item = QTableWidgetItem('Offline')
        self.netTable.setItem(2, row, item)

    def _isExt(self, inFile, trueExtension):
        '''
        Returns true if the extension of the input

        VARS:
            inFile -- The file that comes from inputScene
            trueExtension --  The extesion that you are expecting
        '''
        if os.path.splitext(str(inFile))[1].split('.')[1] == trueExtension:
            return True
        else:
            return False

    def criticalMessage(self, title, message):
        '''
        Pop up critical message window

        VARS:
            title -- Title of window
            message -- message to display
        '''
        msg = QMessageBox()
        msg.critical(None, title, unicode(message))

    def _gatherInfo(self):
        '''Gather information about the current job'''
        #self.ui.cancelRender.setEnabled(True)
        #self.ui.render.setEnabled(False)
        if self.ui.inputOutputDir.text() == '':
            self.criticalMessage("No Output Directory Specified", "You must specify an output directory to send the rendered images to.")
        else:
            if self.ui.inputScene.text() == '':
                self.criticalMessage("No Input File Specified", "You must specify an input file to render.")
            elif not os.path.isfile(self.ui.inputScene.text()):
                self.criticalMessage("Input File Error","You must specify an input file to render, not a path.")
            else:
                self.job = self.ui.inputJobName.text()
                self.sFrame = self.ui.inputStartFrame.text()
                self.eFrame = self.ui.inputEndFrame.text()
                self.bFrame = self.ui.inputByFrame.text()
                self.scene = self.ui.inputScene.text()

                #setup mentalray if activated
                if self.ui.useMentalRay.isChecked():
                    self.rayFlag = '-r mr -v 5 -rt 10'
                else:
                    self.rayFlag = ''

                # get information from the drop down menu
                if self.software.currentText() == 'Maya 2008':
                    # make sure that we are looking at maya extensions
                    #if not self._isExt(self.scene, 'ma') or self._isExt(self.scene, 'mb'):
                        #self.criticalMessage("Bad Input File", "You are rendering with Maya please select a Maya scene.")
                    #else:
                    self.command = '/usr/autodesk/maya2008-x64/bin/Render'

                elif self.software.currentText() == 'Maya 2009':
                    #if not self._isExt(self.scene, 'ma') or self._isExt(self.scene, 'mb'):
                        #self.criticalMessage("Bad Input File", "You are rendering with Maya please select a Maya scene.")
                    #else:
                    self.command = '/usr/autodesk/maya2009-x64/bin/Render'

                elif self.software.currentText() == 'Shake':
                    self.command = '/opt/shake/bin/shake'

                self.jobName = self.ui.inputJobName.text()
                self.outputDir = self.ui.inputOutputDir.text()
                self.logDir = self.ui.inputLogDir.text()
                self.priority = int(self.ui.inputJobPriority.text())

                if self.jobName == '':
                    self.criticalMessage("Missing Job Name", "You're job needs a name")
                else:
                    for frame in range(int(self.sFrame),int(self.eFrame)+1, int(self.bFrame)):
                        if self.rayFlag == '':
                            self.que.put([self.jobName, frame, '%s -s %s -e %s  -rd %s %s' % (self.command, frame, frame, self.outputDir, self.scene)], self.priority)
                        else:
                            self.que.put([self.jobName, frame, '%s %s -s %s -e %s  -rd %s %s' % (self.command, self.rayFlag, frame, frame, self.outputDir, self.scene)], self.priority)

                    self.updateStatus('QUEUE', '%s frames waiting to render' % self.que.size(), 'brown')
                    #self.initJob()

    def initJob(self):
        '''Get an item from the que and send it to a client'''
        # tell the clients that the que is running
        for host in self.hosts:
            thread = WorkerThread(host[1], QUE_PORT, self)
            self.connect(thread, SIGNAL("WORK_COMPLETE"), self.workComplete)
            self.connect(thread, SIGNAL("SENDING_WORK"), self.workSent)
            self.connect(thread, SIGNAL("QUE_EMPTY"), self.queEmpty)
            thread.run()

    def queEmpty(self, host):
        '''Inform the user that the que is empty and rendering is complete'''
        self.updateStatus('JOB', '<b>%s has reported rendering complete!</b>' % host, 'red')

    def workSent(self, work):
        '''Inform the user that a job is being sent'''
        self.updateStatus('NETWORK', 'Sending frame %s from job %s to %s' % (work[2], work[1], work[0]), 'green')

    def workComplete(self, worker):
        '''Inform the user of done frames'''
        ip = worker[0]
        job = worker[1]
        frame = worker[2]
        self.updateStatus('QUEUE', '%s completed frame %s of job %s' % (ip, frame, job), 'brown')

    def killRender(self):
        '''Kill the current job'''
        self.ui.cancelRender.setEnabled(False)
        self.que.emptyQue()
        # send the kill job to clients !
        self.updateStatus('QUEUE', '%s frames waiting to render' % self.que.size(), 'brown')
        self.ui.render.setEnabled(True)

    def _findHosts(self):
        '''Get hosts via broadcast packet, add them to self.hosts'''
        self.updateStatus('NETWORK', 'Searching for hosts...', 'green')
        findHosts = BroadcastServer(self)
        self.connect(findHosts, SIGNAL("gotNode"), self.addHostFromBroadcast)
        self.connect(findHosts,  SIGNAL("DONE"),  self._doneFindingHosts)
        findHosts.start()

    def _doneFindingHosts(self):
        '''Functions to run when done finding hosts'''
        # inform the user of the number of hosts found
        self.updateStatus('NETWORK', 'Found %i new hosts, search complete.' % self.foundHosts, 'green')
        self.foundHosts = 0

    def updateStatus(self, section, msg, color='black'):
        '''
        Update the ui's status window

        VARS:
            section (string)-- The section to report from (ex. NETWORK)
            msg (string) - The message to post
            color (string) - The color name or hex value to set the section
        '''
        self.ui.status.append('<font color=%s><b>%s</b></font> - %s' % (color, section, msg))


app = QApplication(sys.argv)
ui = RC1()
ui.show()
app.exec_()
