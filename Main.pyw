#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Jan 12 2009
PURPOSE: Main program to run and manage PyFarm

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
from lib.ui.RC2 import Ui_RC2
from lib.ui.CustomWidgets import *
import lib.ReadSettings as Settings
## general libs
from lib.Que import *
from lib.Network import *
from lib.RenderConfig import *

# setup the required ports (adjust these settings via settings.cfg)
QUE_PORT = Settings.Network().QuePort()
BROADCAST_PORT = Settings.Network().BroadcastPort()
STDOUT_PORT = Settings.Network().StdOutPort()
STDERR_PORT = Settings.Network().StdErrPort()
SIZEOF_UINT16 = Settings.Network().Unit16Size()
SERVE_FROM = Settings.Network().MasterAddress()

LOCAL_SOFTWARE = {}
software = SoftwareInstalled()

# find the installed software and add it the LOCAL_SOFTWARE
LOCAL_SOFTWARE.update(software.maya())
LOCAL_SOFTWARE.update(software.houdini())
LOCAL_SOFTWARE.update(software.shake())

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


class RC2(QMainWindow):
    '''
    This is the controlling class for the main gui

    NOTES:
        + Functions that are CAPITALIZED from the start are primary
        functions.  For example a function that brings up a file browsing
        gui would be a primary function

        + Functions that do not fall into the above category are utility
        functions.  They are used more often and over a broader spectrum.
        For example the setStartFrame is called whenever a new start frame is declared.
    '''
    def __init__(self):
        super(RC2, self).__init__()

        # setup UI
        self.ui = Ui_RC2()
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

        # populate the avaliable list of renderers
        self.UpdateSoftwareList()

        # setup ui vars
        self.hosts = []
        self.foundHosts = 0
        self.ui.networkTable.setAlternatingRowColors(True)
        self.netTable = self.ui.networkTable
        self.netTable.horizontalHeader().setStretchLastSection(True)
        self.message = QString()
        self.que = QUE

        # Display information about pyfarm and support
        #self.infoMessage('Welcome to PyFarm -- Release Candidate 1', 'Thank you for testing PyFarm!  Please direct all inquiries about this softare to the homepage @ http://www.opalmer.com/pyfarm')

        # make signal connections
        ## ui signals
        self.connect(self.ui.render, SIGNAL("pressed()"), self.initJob)
        self.connect(self.ui.findHosts, SIGNAL("pressed()"), self._findHosts)
        self.connect(self.ui.queryQue, SIGNAL("pressed()"), self.queryQue)
        self.connect(self.ui.emptyQue, SIGNAL("pressed()"), self.emptyQue)
        self.connect(self.ui.enque, SIGNAL("pressed()"), self._gatherInfo)
        self.connect(self.ui.loadQue, SIGNAL("triggered()"), self._loadQue)
        self.connect(self.ui.saveQue, SIGNAL("triggered()"), self._saveQue)
        self.connect(self.ui.currentJobs, SIGNAL("customContextMenuRequested(const QPoint &)"), self.currentJobsContextMenu)

        # connect ui widgets related to global job info
        self.connect(self.ui.inputStartFrame, SIGNAL("valueChanged(int)"), self.setStartFrame)
        self.connect(self.ui.inputEndFrame, SIGNAL("valueChanged(int)"), self.setEndFrame)
        self.connect(self.ui.inputByFrame, SIGNAL("valueChanged(int)"), self.setByFrame)
        self.connect(self.ui.inputJobName, SIGNAL("editingFinished()"), self.setJobName)
        self.connect(self.ui.inputJobPriority, SIGNAL("valueChanged(int)"), self.setJobPriority)
        self.connect(self.ui.softwareSelection, SIGNAL("currentIndexChanged(const QString&)"), self.SetSoftware)

        # connect specific render option vars
        ## maya
        self.connect(self.ui.mayaBrowseForScene, SIGNAL("pressed()"), self.BrowseForInput)
        self.connect(self.ui.mayaBrowseForOutputDir, SIGNAL("pressed()"), self.setMayaImageOutDir)
        self.connect(self.ui.mayaBrowseForProject, SIGNAL("pressed()"), self.setMayaProjectFile)
        self.connect(self.ui.useMentalRay, SIGNAL("stateChanged(int)"), self.setMayaMentalRay)

        # connect ui vars for
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
#        self.ui.inputJobName.setText('rayTest'

    def globalPoint(self, widget, point):
        '''
        Return the global position for a given point
        '''
        return widget.mapToGlobal(point)
        #print pos.manhattanLength()
        #print pos.x
        #print pos.y
        #return QPoint.manhattanLength(pos)

    def CreateContextMenus(self):
        '''
        Create the custom context menus in advance so
        they will not need to be created later.
        '''
        # create a context menu for the job table
        jobContext = QMenu()
        jobContext.addAction('Hello')
        self.jobContextMenu = jobContext

    def currentJobsContextMenu(self, pos):
        menu = QMenu()
        menu.addAction('Job Details')
        menu.addAction('Remove Job')
        menu.addAction('Error Logs')
        #self.absolutePoint(pos)
        menu.exec_(self.globalPoint(self.ui.currentJobs, pos))

    def setStartFrame(self, frame):
        '''Set the start frame value'''
        self.sFrame = frame

    def setEndFrame(self, frame):
        '''Set the end frame value'''
        self.eFrame = frame

    def setByFrame(self, frame):
        '''Set the by frame value'''
        self.byFrame = frame

    def setJobName(self):
        '''Set the job name'''
        self.jobName = self.ui.inputJobName.text()
        print self.jobName

    def setJobPriority(self, priority):
        '''Set the job priority'''
        self.jobPriority = priority

    def BrowseForInput(self):
        '''
        Given an input program, present a search dialog
        so the user can search for the scene/script/etc.
        '''
        render_file = QFileDialog.getOpenFileName(\
            None,
            self.trUtf8("Select File To Render"),
            QString(),
            self.trUtf8(self.fileGrep),
            None,
            QFileDialog.Options(QFileDialog.DontResolveSymlinks))

        self.scene.setText(render_file)

    def SetSoftware(self, newSoftware):
        '''
        If the software selction is changed, setup the
        relevant info.  This includes the sofware generic name (maya),
        command, file grep list, scene ui ref, etc.

        OUTPUTS:
            self.command -- the command used to render
            self.programName -- the common name of the program
            self.fileGrep -- file grep used to search for files to render
            self.scene -- path to ui component used to hold the file to render
        '''
        # convert the QSting to a string
        selected_software = str(newSoftware)
        self.command = LOCAL_SOFTWARE[selected_software].split('::')[0]
        self.program_name = LOCAL_SOFTWARE[selected_software].split('::')[1]
        self.fileGrep = LOCAL_SOFTWARE[selected_software].split('::')[2]
        self.widgetIndex = int(LOCAL_SOFTWARE[selected_software].split('::')[3])

        self.ui.optionStack.setCurrentIndex(self.widgetIndex)
        # if we are using maya
        if self.program_name == 'maya':
            self.ui.optionStack.setCurrentWidget
            self.scene = self.ui.mayaScene
            self.browseForScene = self.ui.mayaBrowseForScene

        # if we are using houdini
        elif self.program_name == 'houidini':
            self.scene = self.ui.houdiniFile
            self.browseForScene = self.ui.houdiniBrowseForScene

        # if we are using shake
        elif self.program_name == 'shake':
            self.scene = self.ui.shakeScript
            self.browseForScene = self.ui.shakeBrowseForScript

    def UpdateSoftwareList(self):
        '''
        Given a software list of installed software, add
        it the list of avaliable software to render with.
        '''
        for (software, path) in LOCAL_SOFTWARE.items():
           self.ui.softwareSelection.addItem(str(software))

        self.SetSoftware(self.ui.softwareSelection.currentText())

################################
################################
## BEGIN Maya Settings
################################
################################

    def setMayaImageOutDir(self):
        '''
        Browse for an output Directory, send the selection to
        the input widget.
        '''
        outDir = QFileDialog.getExistingDirectory(\
            None,
            self.trUtf8("Select A Directory"),
            QString(),
            QFileDialog.Options(QFileDialog.DontResolveSymlinks | QFileDialog.ShowDirsOnly))

        if outDir != '':
            self.ui.mayaOutputDir.setText(outDir)

    def setMayaProjectFile(self):
        '''
        Set the maya project file
        '''
        projectFile = QFileDialog.getOpenFileName(\
            None,
            self.trUtf8("Select Your Maya Project File"),
            QString(),
            self.trUtf8("Maya Project File(*.mel)"),
            None,
            QFileDialog.Options(QFileDialog.DontResolveSymlinks))

        if projectFile != '':
            self.ui.mayaProjectFile.setText(projectFile)

    def setMayaMentalRay(self, val):
        '''
        Given a value setup the global var
        '''
        if val == 2:
            self.useMentalRay = 1
        else:
            self.useMentalRay = 0

################################
################################
## END Maya Settings
################################
################################

    def _loadQue(self):
        '''Load que information from a file'''
        queInFile = QFileDialog.getOpenFileName(\
            None,
            self.trUtf8("Select Queue To Load"),
            QString(),
            self.trUtf8("PyFarm Queue(*.pyq)"),
            None,
            QFileDialog.Options(QFileDialog.DontResolveSymlinks))

        QueFile = QFile(queInFile)
        QueFile.open(QIODevice.ReadOnly)

    def _saveQue(self):
        '''Save the current que for later use'''
        queOutFile = QFileDialog.getSaveFileName(\
            None,
            self.trUtf8("Save Queue To File"),
            QString(),
            self.trUtf8("PyFarm Queue(*.pyq)"),
            None)

        self.updateStatus('SETTINGS', 'Saving que to file...', 'purple')
        QueFile = QFile(queOutFile)
        QueFile.open(QIODevice.WriteOnly)

        for i in range(1, QUE.qsize()+1):
            que = QUE.get()
            QueFile.write('%s:::%s:::%s\n' % (str(que[0]), str(que[1]), str(que[2])))
            QUE.put(que)

        QueFile.close()
        self.updateStatus('SETTINGS', 'Saving complete.', 'purple')

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

    def infoMessage(self, title, message):
        '''
        Pop up critical message window

        VARS:
            title -- Title of window
            message -- message to display
        '''
        msg = QMessageBox()
        msg.information(None, title, unicode(message))

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
                    self.command = '/usr/local/bin/Render'

                elif self.software.currentText() == 'Maya 2009':
                    #if not self._isExt(self.scene, 'ma') or self._isExt(self.scene, 'mb'):
                        #self.criticalMessage("Bad Input File", "You are rendering with Maya please select a Maya scene.")
                    #else:
                    self.command = '/usr/autodesk/maya2009-x64/bin/Render'

                elif self.software.currentText() == 'Shake':
                    self.command = '/opt/shake/bin/shake'

                self.jobName = self.ui.inputJobName.text()
                self.outputDir = self.ui.inputOutputDir.text()
                self.projectFile = self.ui.inputProject.text()
                self.priority = int(self.ui.inputJobPriority.text())

                if self.jobName == '':
                    self.criticalMessage("Missing Job Name", "You're job needs a name")
                else:
                    if self.software.currentText() != 'Shake':
                        for frame in range(int(self.sFrame),int(self.eFrame)+1, int(self.bFrame)):
                            if self.rayFlag == '':
                                self.que.put([self.jobName, frame, '%s -proj %s -s %s -e %s -rd %s %s' % (self.command, self.projectFile, frame, frame, self.outputDir, self.scene)], self.priority)
                            else:
                                self.que.put([self.jobName, frame, '%s %s -proj %s -s %s -e %s  -rd %s %s' % (self.command, self.rayFlag, self.projectFile, frame, frame, self.outputDir, self.scene)], self.priority)
                    else:
                        for frame in range(int(self.sFrame),int(self.eFrame)+1, int(self.bFrame)):
                            self.que.put([self.jobName, frame, '%s -v -t %s-%sx1 -exec %s' % (self.command, frame, frame, self.scene)], self.priority)

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
ui = RC2()
ui.show()
app.exec_()
