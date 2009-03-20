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
from time import time, sleep
from random import randrange
# From PyQt
from PyQt4.QtCore import *
from PyQt4.QtGui import *
from PyQt4.QtNetwork import *
# From PyFarm
## ui components
import lib.Info as Info
import lib.ReadSettings as Settings
from lib.ui.RC3 import Ui_RC3
from lib.ui.CustomWidgets import *
from lib.Process import FakeProgressBar
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

__VERSION__ = 'RC3'

class WorkerThread(QThread):
    '''Used work out to a worker TCP server'''
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


class Main(QMainWindow):
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
        super(Main, self).__init__()

        # setup UI
        self.ui = Ui_RC3()
        self.ui.setupUi(self)

        # setup some basic colors
        self.red = QColor(255, 0, 0)
        self.green = QColor(0, 128, 0)
        self.blue = QColor(0, 0, 255)
        self.purple = QColor(128, 0, 128)
        self.white = QColor(255, 255, 255)
        self.black = QColor(0, 0, 0)
        self.orange = QColor(255, 165, 0)

        # add external libs
        self.netTableLib = NetworkTable()

        # inform the user of their settings
        self.updateStatus('SETTINGS', 'Got settings from ./settings.cfg', 'purple')
        self.updateStatus('SETTINGS', 'Server IP: %s' % SERVE_FROM, 'purple')
        self.updateStatus('SETTINGS', 'Broadcast Port: %s' % BROADCAST_PORT, 'purple')
        self.updateStatus('SETTINGS', 'StdOut Port: %s' % STDOUT_PORT, 'purple')
        self.updateStatus('SETTINGS', 'StdErr Port: %s' % STDERR_PORT, 'purple')
        self.updateStatus('SETTINGS', 'Que Port: %s' % QUE_PORT, 'purple')

        for (program, value) in LOCAL_SOFTWARE.items():
            outVal = value.split("::")[0]
            self.updateStatus('SETTINGS', '%s: %s' % (program, outVal), 'purple')

        # populate the avaliable list of renderers
        self.UpdateSoftwareList()

        # setup ui vars
        self.hosts = []
        self.foundHosts = 0
        self.ui.networkTable.setAlternatingRowColors(True)
        self.netTable = self.ui.networkTable
        self.netTable.horizontalHeader().setStretchLastSection(True)
        self.ui.currentJobs.horizontalHeader().setStretchLastSection(True)
        self.message = QString()
        self.que = QUE

        # Display information about pyfarm and support
        #self.infoMessage('Welcome to PyFarm -- Release Candidate 1', 'Thank you for testing PyFarm!  Please direct all inquiries about this softare to the homepage @ http://www.opalmer.com/pyfarm')

        # make signal connections
        ## ui signals
        self.connect(self.ui.render, SIGNAL("pressed()"), self.initJob)
        self.connect(self.ui.findHosts, SIGNAL("pressed()"), self.findHosts)
        self.connect(self.ui.queryQue, SIGNAL("pressed()"), self.queryQue)
        self.connect(self.ui.emptyQue, SIGNAL("pressed()"), self.emptyQue)
        self.connect(self.ui.enque, SIGNAL("pressed()"), self.SubmitToQue)
        self.connect(self.ui.loadQue, SIGNAL("triggered()"), self._loadQue)
        self.connect(self.ui.saveQue, SIGNAL("triggered()"), self._saveQue)
        self.connect(self.ui.currentJobs, SIGNAL("customContextMenuRequested(const QPoint &)"), self.currentJobsContextMenu)

        # connect ui widgets related to global job info
        self.connect(self.ui.inputJobName, SIGNAL("editingFinished()"), self.setJobName)
        self.connect(self.ui.softwareSelection, SIGNAL("currentIndexChanged(const QString&)"), self.SetSoftware)

        # connect specific render option vars
        ## maya
        self.connect(self.ui.mayaBrowseForScene, SIGNAL("pressed()"), self.BrowseForInput)
        self.connect(self.ui.mayaBrowseForOutputDir, SIGNAL("pressed()"), self.setMayaImageOutDir)
        self.connect(self.ui.mayaBrowseForProject, SIGNAL("pressed()"), self.setMayaProjectFile)
        self.connect(self.ui.mayaRenderLayers, SIGNAL("customContextMenuRequested(const QPoint &)"), self.mayaRenderLayersListMenu)
        self.connect(self.ui.mayaAddCamera, SIGNAL("clicked()"), self.mayaCameraEditMenu)

        ## houdini
        self.connect(self.ui.houdiniOutputCustomImageRes, SIGNAL("stateChanged(int)"), self.setHoudiniCustomResState)
        self.connect(self.ui.houdiniOutputKeepAspect, SIGNAL("stateChanged(int)"), self.setHoudiniKeepAspectRatio)
        self.connect(self.ui.houdiniOutputWidth, SIGNAL("valueChanged(int)"), self.setHoudiniWidth)
        self.connect(self.ui.houdiniOutputPixelAspect, SIGNAL("valueChanged(double)"), self.setHoudiniAspectRatio)
        self.connect(self.ui.houdiniBrowseForScene, SIGNAL("pressed()"), self.setHoudiniScene)
        self.connect(self.ui.houdiniNodeList, SIGNAL("customContextMenuRequested(const QPoint &)"), self.houdiniNodeListMenu)

        # connect ui vars for
        ## network section signals
        self.connect(self.ui.disableHost, SIGNAL("pressed()"), self._disableHosts)
        self.connect(self.ui.addHost, SIGNAL("pressed()"), self._customHostDialog)
        self.connect(self.ui.removeHost, SIGNAL("pressed()"), self._removeSelectedHost)

        # GENERATE SOME FAKE DATA
        self.connect(self.ui.currentJobs, SIGNAL("cellActivated(int,int)"), self.fakePrintTableSelection)
        self.fakeSetup()
        self.InitialStatus()
        #self.findHosts()

################################
## BEGIN Context Menus
################################
    def globalPoint(self, widget, point):
        '''
        Return the global position for a given point
        '''
        return widget.mapToGlobal(point)

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
        menu.addAction('Job Details', self.JobDetails)
        menu.addAction('Remove Job', self.RemoveJobFromTable)
        #menu.addAction('Error Logs')
        menu.exec_(self.globalPoint(self.ui.currentJobs, pos))

    def houdiniNodeListMenu(self, pos):
        '''
        Popup a custom context menu for the houdini
        node list
        '''
        menu = QMenu()
        menu.addAction('Add Node', self.houdiniAddOutputNode)
        menu.addAction('Remove Node', self.houdiniNodeListRemoveSelected)
        menu.addAction('Empty List', self.houdiniNodeListEmpty)
        menu.exec_(self.globalPoint(self.ui.houdiniNodeList, pos))

    def mayaCameraEditMenu(self):
        '''
        Popup small menu to edit the camera list
        '''
        widget = self.ui.mayaAddCamera
        menu = QMenu()
        menu.addAction('Add Camera', self.mayaAddCamera)
        menu.addAction('Remove Camera', self.mayaRemoveCamera)
        menu.addAction('Empty List', self.ui.mayaCamera.clear)
        menu.exec_(self.globalPoint(widget, -widget.mapToParent(-widget.pos())))

    def mayaRenderLayersListMenu(self, pos):
        '''
        Popup a custom context menu for the maya render
        layer list
        '''
        menu = QMenu()
        menu.addAction('Add Layer', self.mayaAddLayer)
        menu.addAction('Remove Layer', self.mayaRenderLayerRemove)
        menu.addAction('Empty List', self.mayaRenderLayerEmpty)
        menu.exec_(self.globalPoint(self.ui.mayaRenderLayers, pos))

#############################
## END Context Menus
################################
## BEGIN General Job Settings
################################
    def setJobName(self):
        '''Set the job name'''
        self.jobName = self.ui.inputJobName.text()

################################
## END General Job Settings
################################
## BEGIN Software Discovery
################################
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

        if self.software_generic == 'maya':
            self.mayaGetLayersAndCams()

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
        try:
            # convert the QSting to a string
            self.software = str(newSoftware)
            self.command = LOCAL_SOFTWARE[self.software].split('::')[0]
            self.software_generic = LOCAL_SOFTWARE[self.software].split('::')[1]
            self.fileGrep = LOCAL_SOFTWARE[self.software].split('::')[2]
            self.widgetIndex = int(LOCAL_SOFTWARE[self.software].split('::')[3])
            self.ui.optionStack.setCurrentIndex(self.widgetIndex)

            # if we are using maya
            if self.software_generic == 'maya':
                self.ui.optionStack.setCurrentWidget
                self.scene = self.ui.mayaScene
                self.browseForScene = self.ui.mayaBrowseForScene

            # if we are using houdini
            elif self.software_generic == 'houidini':
                self.scene = self.ui.houdiniFile
                self.browseForScene = self.ui.houdiniBrowseForScene

            # if we are using shake
            elif self.software_generic == 'shake':
                self.scene = self.ui.shakeScript
                self.browseForScene = self.ui.shakeBrowseForScript

        # if we can't find the software, return an error
        except KeyError:
            self.criticalMessage('Could Not Find Programs', 'Sorry but we could not find any software installed on your system to render with.\n\nExit Code: 1', 1)

    def UpdateSoftwareList(self):
        '''
        Given a software list of installed software, add
        it the list of avaliable software to render with.
        '''
        for (software, path) in LOCAL_SOFTWARE.items():
           self.ui.softwareSelection.addItem(str(software))

        self.SetSoftware(self.ui.softwareSelection.currentText())

################################
## END Software Discovery
################################
## BEGIN Maya Settings
################################
    def mayaRemoveCamera(self):
        '''
        Remove the currently selected camera
        '''
        widget = self.ui.mayaCamera
        widget.removeItem(widget.currentIndex())

    def mayaAddCamera(self):
        '''
        Add a custom camera to maya
        '''
        dialog = CustomObjectDialog(self)
        self.connect(dialog, SIGNAL("objectName"), self.ui.mayaCamera.addItem)
        dialog.exec_()

    def mayaAddLayer(self):
        '''
        Add a custom layer to maya
        '''
        dialog = CustomObjectDialog(self)
        self.connect(dialog, SIGNAL("objectName"), self.ui.mayaRenderLayers.addItem)
        dialog.exec_()

    def mayaGetLayersAndCams(self):
        '''
        Given a file get of of the layers and cameras
        '''
        self.mayaEmptyLayersAndCameras()

        if self.scene.text() == '':
            pass
        else:
            ext = Info.File(self.scene.text()).ext()
            #scene = open(self.scene.text(), 'r')
            layerRegEx = QRegExp(r"""createNode renderLayer -n .+""")
            cameraRegEx = QRegExp(r"""createNode camera -n .+""")

            if ext != 'ma':
                self.infoMessage('Cannot Auto Detect Cameras and Layers', 'Sorry we could not detect your camers and layers automatically.  Please use a Maya ASCII file if you wish to use auto detection.')
            else:
                multiPass = MayaCamAndLayers(self.scene.text())
                self.connect(multiPass, SIGNAL("gotMayaLayer"), self.ui.mayaRenderLayers.addItem)
                self.connect(multiPass, SIGNAL("gotMayaCamera"), self.ui.mayaCamera.addItem)
                multiPass.run()

    def mayaEmptyLayersAndCameras(self):
        '''
        Empty render layer list and the camera list
        '''
        self.ui.mayaRenderLayers.clear()
        self.ui.mayaCamera.clear()

    def mayaRenderLayerRemove(self):
        '''
        Remove the currently selected layer(s) from
        the node list.
        '''
        for item in self.ui.mayaRenderLayers.selectedItems():
            item.setHidden(1)

    def mayaRenderLayerEmpty(self):
        '''
        Clear all nodes from the node list
        '''
        self.ui.mayaRenderLayers.clear()

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
## END Maya Settings
################################
## BEGIN Houdini Settings
################################
    def houdiniAddOutputNode(self):
        '''
        Add an output node too the node list
        '''
        dialog = CustomObjectDialog(self)
        self.connect(dialog, SIGNAL("objectName"), self.ui.houdiniNodeList.addItem)
        dialog.exec_()

    def houdiniNodeListRemoveSelected(self):
        '''
        Remove the currently selected node(s) from
        the node list.
        '''
        for item in self.ui.houdiniNodeList.selectedItems():
            item.setHidden(1)

    def houdiniNodeListEmpty(self):
        '''
        Clear all nodes from the node list
        '''
        self.ui.houdiniNodeList.clear()

    def setHoudiniScene(self):
        '''
        Display a search gui then set the houdini scene.
        Lastly, gather all of the output nodes from the file.
        '''
        projectFile = QFileDialog.getOpenFileName(\
            None,
            self.trUtf8("Select Your Houdini Scene"),
            QString(),
            self.trUtf8(self.fileGrep),
            None,
            QFileDialog.Options(QFileDialog.DontResolveSymlinks))

        if projectFile != '':
            self.ui.houdiniFile.setText(projectFile)
            self.houdiniFindOutputNodes(projectFile)

    def houdiniFindOutputNodes(self, inFile):
        '''
        Given an input file, find all of the output nodes and
        display them in the node table.
        '''
        hou = open(inFile)
        exp = QRegExp(r"""[0-9]+out/[0-9a-zA-Z]+[.]parm""")

        for line in hou.readlines():
            if not exp.indexIn(line):
                self.ui.houdiniNodeList.addItem(QString(line.split('/')[1].split('.')[0]))

    def setHoudiniCustomResState(self, newState):
        '''
        Based on user pref, setup the ui for custom image resolution
        '''
        widgets = [\
                    self.ui.houdiniOutputKeepAspect,
                    self.ui.houdiniOutputWidth,
                    self.ui.houdiniOutputWidthLabel,
                    self.ui.houdiniOutputPixelAspect,
                    self.ui.houdiniOutputPixelAspectLabel]

        if newState:
            for widget in widgets:
                widget.setEnabled(0)

            self.ui.houdiniOutputHeight.setEnabled(0)
            self.ui.houdiniOutputHeightLabel.setEnabled(0)

        else:
            for widget in widgets:
                widget.setEnabled(1)

            if self.houdiniKeepAspectRatio():
                self.ui.houdiniOutputHeight.setEnabled(0)
                self.ui.houdiniOutputHeightLabel.setEnabled(0)
            else:
                self.ui.houdiniOutputHeight.setEnabled(1)
                self.ui.houdiniOutputHeightLabel.setEnabled(1)

    def setHoudiniKeepAspectRatio(self, newState):
        '''
        Given newState set the global var accordingly and
        enable/disable the appropriate combo box.
        '''
        # if the box is checked, keep the aspect ratio
        if newState:
            self.ui.houdiniOutputHeight.setEnabled(0)
            self.ui.houdiniOutputHeightLabel.setEnabled(0)
        else:
            self.ui.houdiniOutputHeight.setEnabled(1)
            self.ui.houdiniOutputHeightLabel.setEnabled(1)

    def setHoudiniWidth(self, width):
        '''
        Set the width of the output image
        '''
        if not self.houdiniKeepAspectRatio():
            pass
        else:
            a = float(width)
            b = float(self.houdiniAspectRatio())
            self.ui.houdiniOutputHeight.setValue(a//b)

    def setHoudiniHeight(self, height):
        '''
        Set the height of the output image
        '''
        try:
            if not self.houdiniKeepAspect:
                pass
        except AttributeError:
            self.houdiniKeepAspect = 1

    def setHoudiniAspectRatio(self, aspectRatio):
        '''
        Set the aspect ratio of the render
        '''
        self.setHoudiniWidth(self.ui.houdiniOutputWidth.value())

    def houdiniAspectRatio(self):
        '''
        Return the current aspect ratio from
        the interface.
        '''
        return self.ui.houdiniOutputPixelAspect.value()

    def houdiniKeepAspectRatio(self):
        '''
        Return the state of the keep aspect ratio check
        box.
        '''
        return self.ui.houdiniOutputKeepAspect.isChecked()

################################
## END Houdini Settings
################################
## BEGIN Que Management
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

################################
## END Que Management
################################
## BEGIN Host Management
################################
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
        modName = 'Main.addHost'
        # check to make sure the host is valid
        if ResolveHost(host) == 'BAD_HOST':
            self.warningMessage("Bad host or IP", "Sorry %s could not be resolved, please check your entry and try again." % host)
            print "PyFarm :: %s :: Bad host or IP, could not add %s " % (modName, host)
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
                print "PyFarm :: %s :: Found host %s" % (modName, self.currentHost)
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

    def _disableHosts(self):
        row = self._getHostSelection()[0]
        item = QTableWidgetItem('Offline')
        self.netTable.setItem(2, row, item)

################################
## END Host Management
################################
## BEGIN Job/Que System
################################
    def SubmitToQue(self):
        '''Gather information about the current job'''
        # first make a copy of self.software
        config = ConfigureCommand(LOCAL_SOFTWARE)
        jobID = self.runChecks()

        if jobID:
            startSize = self.que.size()
            sFrame = self.ui.inputStartFrame.value()
            eFrame = self.ui.inputEndFrame.value()
            bFrame = self.ui.inputByFrame.value()
            priority = self.ui.inputJobPriority.value()
            jobName = str(self.ui.inputJobName.text())
            outDir = str(self.ui.mayaOutputDir.text())
            project = str(self.ui.mayaProjectFile.text())
            scene = str(self.scene.text())
            commands = []

            if self.software_generic == 'maya':
                renderer = str(self.ui.mayaRenderer.currentText())
                layers = self.ui.mayaRenderLayers.selectedItems()
                camera = str(self.ui.mayaCamera.currentText())

                if len(layers) >= 1:
                    for layer in layers:
                        for command in config.maya(str(self.software), sFrame, eFrame, bFrame,\
                            renderer, scene, str(layer.text()), camera, outDir, project):
                                self.que.put([jobName, command[0], command[1], str(command[2])], priority)
                                frame = QString(command[0])
                                cmd = QString(str(command[2]))
                                #self.que.put([jobName, frame, cmd], priority)
                else:
                    for command in config.maya(str(self.software), sFrame, eFrame, bFrame,\
                        renderer, scene, '', camera, outDir, project):
                            frame = QString(command[0])
                            cmd = QString(str(command[2]))
                            print frame
                            print cmd
                            self.que.put([jobName, frame, cmd], priority)

                newFrames = self.que.size()-startSize

                self.updateStatus('QUE', 'Added %s frames to job %s(ID: %s) with priority %s' % \
                                  (newFrames, jobName, jobID, priority), 'black')

            elif self.software_generic == 'houdini':
                pass
            elif self.software_generic == 'shake':
                pass
        else:
            pass

    def runChecks(self):
        '''
        Check to be sure the user has entered the minium values
        '''
        if self.scene.text() == '':
            self.warningMessage('Missing File', 'You must provide a file to render')
            return 0
        elif not os.path.isfile(self.scene.text()):
            self.warningMessage('Please Select a File', 'You must provide a file to render, links or directories will not suffice.')
            return 0
        else:
            try:
                if self.jobName == '':
                    self.warningMessage('Missing Job Name', 'You name your job before you rendering')
                    return 0
            except AttributeError:
                self.warningMessage('Missing Job Name', 'You name your job before you rendering')
                return 0
            finally:
                # get a random number and return the hexadecimal value
                return Info.Numbers().randhex()

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

    def findHosts(self):
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

################################
## END Job/Que System
################################
## BEGIN Status/Message System
################################

    def criticalMessage(self, title, message, code):
        '''
        Pop up critical message window

        VARS:
            title (str) -- Title of window
            message (str) -- message to display
            code (int) -- exit code to give to sys.exit
        '''
        QMessageBox().critical(self, title, unicode(message))
        sys.exit(code)

    def warningMessage(self, title, message):
        '''
        Pop up critical message window

        VARS:
            title -- Title of window
            message -- message to display
        '''
        msg = QMessageBox()
        msg.warning(self, title, unicode(message))

    def infoMessage(self, title, message):
        '''
        Pop up critical message window

        VARS:
            title -- Title of window
            message -- message to display
        '''
        msg = QMessageBox()
        msg.information(self, title, unicode(message))

    def updateStatus(self, section, msg, color='black'):
        '''
        Update the ui's status window

        VARS:
            section (string)-- The section to report from (ex. NETWORK)
            msg (string) - The message to post
            color (string) - The color name or hex value to set the section
        '''
        self.ui.status.append('<font color=%s><b>%s</b></font> - %s' % (color, section, msg))

    def SetStatus(self, obj, txt, color='black'):
        '''Set the text of a specific status label'''
        R = '#FF0000'
        G = 'green'
        B = '#0000FF'
        if txt == 'Inactive':
            color = R
        elif txt == 'Active':
            color = G
        elif txt == 0:
            color = R
        elif txt == "No Frames In Que":
            color = R

        # format the cpu time
        if self.ui.status_general_cpu_time_used != 0:
            # get the latest cpu time, add it to the current usage, update the ui
            #txt = Elapsed(txt, currentTime)
            pass

        text = QString('<FONT COLOR="%s">%s</FONT>' % (color, txt))
        obj.setText(text)

    def InitialStatus(self):
        '''Set the initial status states'''
        a = 'Active'
        i = 'Inactive'
        nf = 'No Frames In Que'
        status_objects = [{self.ui.status_pyfarm_master : a, self.ui.status_pyfarm_network : a, self.ui.status_pyfarm_que : a},
                                    {self.ui.status_network_connected : 4, self.ui.status_network_active : 4},
                                    {self.ui.status_que_system_status : a, self.ui.status_que_system_failure : '5%'},
                                    {self.ui.status_que_jobs_waiting : 0, self.ui.status_que_jobs_running : 4, self.ui.status_que_jobs_failed : 1, self.ui.status_que_jobs_complete : 0},
                                    {self.ui.status_que_frames_waiting: 247, self.ui.status_que_frames_running : 4, self.ui.status_que_frames_failed : 15, self.ui.status_que_frames_complete : 27},
                                    {self.ui.status_general_time_remaining : '10m 43s', self.ui.status_general_cpu_time_used : '45m 19s', self.ui.status_general_time_elapsed : '9m 3s',
                                    self.ui.status_general_average_time_per_frame : '1m 23s', self.ui.status_general_shortest_time : '12s', self.ui.status_general_longest_time : '4m 23s',
                                    self.ui.status_general_average_memory_usage : '457 MB', self.ui.status_general_average_frame_size : '4 MB'}]

        for obj in status_objects:
            for key, value in obj.items():
                    self.SetStatus(key, value)

        self.SetStatus(self.ui.status_general_version, "RC3", 'black')
        self.SetStatus(self.ui.status_general_random_wiki, "<a href='http://www.opalmer.com/pyfarm/wiki'>Link</a>", 'black')
################################
## END Status/Message System
################################
## BEGIN Job System Manager
################################
    def fakePrintTableSelection(self, x, y):
        '''Print the current table selection'''
        print "X: %i Y: %i" % (x, y)

    def fakeSetup(self):
        '''Setup the fake information for presentation'''
        self.fakeTableEntries()

    def fakeTableEntries(self):
        '''Add a fake progress bar to the table'''
        jobs = self.ui.currentJobs
        jobNames = ["job1", "job2", "job3", "job4", "job5"]
        self.jobNames = jobNames
        states = [["Waiting", [self.black, self.white]], ["Rendering", [self.white, self.green]], ["Failed", [self.white, self.red]]]
        statusKeys = [["Rendering", [self.black, self.orange]],
                                    ["Rendering", [self.black, self.orange]],
                                    ["Rendering", [self.black, self.orange]],
                                    ["Failed",[self.black, self.red]],
                                    ["Waiting", [self.black, self.white]]
                                    ]

        for row in range(0, len(jobNames)):
            jobs.insertRow(row)
            name = QTableWidgetItem(QString(jobNames[row]))

            # set the status (inluding color)
            status = QTableWidgetItem(statusKeys[row][0])
            status.setTextColor(statusKeys[row][1][0])
            status.setBackgroundColor(statusKeys[row][1][1])

            # set the progress bar
            s = 1
            e = 50
            self.s = s
            self.e = e
            progress = QProgressBar()
            progress.setRange(s, e)
            progress.setValue(1)
            jobs.setItem(row, 0, name)
            jobs.setItem(row, 1, status)
            jobs.setCellWidget(row, 2, progress)
            jobs.resizeColumnsToContents()

            # change the color of the and progress area, based on status
            if jobs.item(row, 1).text() == 'Failed':
                #self.addProgressBar()
                status = QTableWidgetItem(QString("Rendering"))
                status.setTextColor(self.black)
                status.setBackgroundColor(self.orange)
                jobs.setItem(row, 1, status)

                # connect to fake failure
                self.fakeProgressFailure = FakeProgressBarFailure(progress, self)
                self.connect(self.fakeProgressFailure, SIGNAL("increment"), progress.setValue)
                self.connect(self.fakeProgressFailure, SIGNAL("failed"), self.fakeFailRender)
                self.fakeProgressFailure.start()

            elif jobs.item(row, 1).text() == 'Waiting':
                progress.setDisabled(1)
                jobs.setCellWidget(row, 2, progress)
            else:
                fakeProgress = FakeProgressBar(progress, jobNames[row], row, self)
                self.connect(fakeProgress, SIGNAL("increment"), progress.setValue)
                self.connect(fakeProgress, SIGNAL("complete"), self.fakeSetRenderComplete)
                fakeProgress.start()

    def fakeFailRender(self):
        '''Fake a failed render'''
        self.fakeProgressFailure.quit()

        newStatus = QTableWidgetItem(QString("Failed"))
        newStatus.setTextColor(self.black)
        newStatus.setBackgroundColor(self.red)

        newName = QTableWidgetItem(QString(self.jobNames[3]))
        newName.setTextColor(self.black)
        newName.setBackgroundColor(self.red)

        replaceProgress = QTableWidgetItem(QString("FRAME FAILED TO RENDER (SEE THE LOGS)"))
        replaceProgress.setTextColor(self.black)
        replaceProgress.setBackgroundColor(self.red)

        self.ui.currentJobs.setItem(3, 0, newName)
        self.ui.currentJobs.setItem(3, 1, newStatus)
        self.ui.currentJobs.setItem(3, 2, replaceProgress)

        self.fakeStartWaitingRender()

    def fakeStartWaitingRender(self):
        '''Fake a waiting render start'''
        progress = QProgressBar()
        progress.setRange(self.s, self.e)
        progress.setValue(1)

        newStatus = QTableWidgetItem(QString("Rendering"))
        newStatus.setTextColor(self.black)
        newStatus.setBackgroundColor(self.orange)

        self.ui.currentJobs.setItem(4, 1, newStatus)
        self.ui.currentJobs.setCellWidget(4, 2, progress)

        fakeProgress = FakeProgressBar(progress, "MEL Script Test", 4, self)
        self.connect(fakeProgress, SIGNAL("increment"), progress.setValue)
        self.connect(fakeProgress, SIGNAL("complete"), self.fakeSetRenderComplete)
        fakeProgress.start()

    def fakeSetRenderComplete(self, inList):
        '''Change the colors of complere renders'''
        oldName = inList[0]
        row = inList[1]

        newName = QTableWidgetItem(QString(oldName))
        newName.setTextColor(self.white)
        newName.setBackgroundColor(self.green)

        newStatus = QTableWidgetItem(QString("Complete"))
        newStatus.setTextColor(self.white)
        newStatus.setBackgroundColor(self.green)

        progress = QTableWidgetItem()
        progress.setBackgroundColor(self.green)

        self.ui.currentJobs.setItem(row, 0, newName)
        self.ui.currentJobs.setItem(row, 1, newStatus)
        self.ui.currentJobs.setItem(row, 2, progress)

    def JobDetails(self):
        '''Bring up some fake job information'''
        # setup some common vars
        jobs = self.ui.currentJobs
        currentRow = jobs.currentRow()
        jobName = jobs.item(currentRow, 0).text()
        jobStatus = jobs.item(currentRow, 1).text()
        groups = ['af9c477', '3c4cb7d']

        if jobStatus == 'Failed':
            status = QString('<font color="red">Failed</font>')
        elif jobStatus == 'Rendering':
            status = QString('<font color="orange">Rendering</font>')
        elif jobStatus == 'Complete':
            status = QString('<font color="green">Complete</font>')
        else:
            status = QString(jobStatus)

        # setup the widget
        widget = JobDetails()
        widget.ui.jobDetails_job_name.setText(jobName)
        widget.ui.jobDetails_job_status.setText(status)

        for row in range(0, 75):

            progressBar = QProgressBar()
            progressBar.setValue(randrange(2, 80))
            stat = QTableWidgetItem(jobStatus)
            stat.setTextColor(self.red)
            id = QTableWidgetItem(QString(groups[randrange(0, 2)]))
            button =QPushButton(QString("Open"), widget.ui.frameTable)
            self.connect(button, SIGNAL("pressed()"), self.openLog)

            # insert the items into the row
            widget.ui.frameTable.insertRow(int(row))
            widget.ui.frameTable.setItem(row , 0, stat)
            widget.ui.frameTable.setItem(row , 1, id)
            widget.ui.frameTable.setCellWidget(row , 2, progressBar)
            widget.ui.frameTable.setCellWidget(row , 3, button)
            widget.ui.frameTable.setColumnWidth(2, 200)

        widget.exec_()

    def openLog(self):
        '''Open up the frame log and show it to the user'''
        log = open("/farm/projects/PyFarm/trunk/RC3/job4_log.001.txt", 'r')
        widget = LogViewer()

        # add the contents of the log to the gui
        for line in log:
            widget.ui.log.append(line)

        widget.exec_()

    def RemoveJobFromTable(self):
        '''Remove the fake bad job'''
        jobs = self.ui.currentJobs
        jobs.removeRow(jobs.currentRow())

    def AddProgressBar(self, widget, start, end, table, row, col):
        '''Add a progress bar to the following widget'''
        pass

################################
## END Job System Manager
################################
## BEGIN General Utilities
################################
    def shutdownHosts(self):
        '''Shutdown all remote hosts'''
        for host in self.hosts:
            client = AdminClient(host[1], ADMIN_PORT)
            client.shutdown()

    def restartHosts(self):
        '''Restart all remote hosts'''
        for host in self.hosts:
            client = AdminClient(host[1], ADMIN_PORT)
            client.restart()

    def hostStateHelp(self):
        '''
        Inform the user of what/why hosts are restarted or
        shutdown
        '''
        title = QString("Client Action Help")
        help = QString("\
        <p>When closing PyFarm you have two options to deal with\
        remote clients:</p>\
        <p>\
        Select <b><i>yes<i></b> shutdown the remote clients<br>\
        Select <b><i>no<i></b> to restart the remote clients for future use\
        </p>")
        QMessageBox.information(self, title, help)
        self.closeEvent(self)

    def hostExitDialog(self):
        '''Present a host shutdown dialog to the user'''
        return QMessageBox.question(self,
                    QString("Select Client Action"),
                    QString("Would you like to shutdown all remote client nodes?"),
                    QMessageBox.Yes|QMessageBox.No|QMessageBox.Help)

    def closeEvent(self, event):
        '''Run when closing the main gui, used to "cleanup" the program state'''
        exit_dialog = self.hostExitDialog()

        if exit_dialog == QMessageBox.Yes:
            print "PyFarm :: Main.closeEvent :: Shutting Down Clients..."
            self.shutdownHosts()

        elif exit_dialog == QMessageBox.No:
            print "PyFarm :: Main.closeEvent :: Restarting clients..."
            self.restartHosts()

        elif exit_dialog == QMessageBox.Help:
            print "PyFarm :: Main.closeEvent :: Presenting host help"
            self.hostStateHelp()

################################
## END General Utilities
################################

app = QApplication(sys.argv)
main = Main()
main.show()
sys.exit(app.exec_())
