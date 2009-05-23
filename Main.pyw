#!/usr/bin/python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 12 2009
PURPOSE: Main program to run and manage PyFarm

    This file is part of PyFarm.
    Copyright (C) 2008-2009 Oliver Palmer

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
from os.path import dirname
from time import time, sleep
from random import randrange, random
from pprint import pprint

# From PyQt
# widget, subsidgets, and utility imports
from PyQt4.QtGui import QMainWindow, QMessageBox
from PyQt4.QtGui import QTableWidgetItem, QTreeWidgetItem
from PyQt4.QtGui import QColor, QProgressBar, QPushButton, QFileDialog
from PyQt4.QtCore import QDir, QUuid
from PyQt4.QtNetwork import QHostInfo, QHostAddress

wd = dirname(str(QDir(sys.argv[0]).canonicalPath()))
QDir().setCurrent(wd)

# From PyFarm
## settings first
from lib.RenderConfig import *
from lib.ReadSettings import ParseXmlSettings
settings = ParseXmlSettings('settings.xml', 'gui')

import lib.Info as Info
from lib.ui.MainWindow import Ui_MainWindow
from lib.ui.main.CustomWidgets import *
from lib.ui.main.job.Submit import SubmitManager
from lib.ui.main.CloseEvent import CloseEventManager
from lib.ui.main.Software import SoftwareContextManager
from lib.data.Job import JobManager
from lib.data.General import GeneralManager
from lib.network.Admin import AdminClient
from lib.network.Broadcast import BroadcastSender
from lib.network.Status import StatusServer
from lib.network.JobLogging import UdpLoggerServer
from lib.ui.main.job.table.JobTableManager import JobTableManager

__DEVELOPER__ = 'Oliver Palmer'
__VERSION__ = '0.3.210'
__HOMEPAGE__ = 'http://www.pyfarm.net'
__DOCS__ = '%s/wiki' % __HOMEPAGE__
__WIKI__ = __DOCS__
__UUID__ = QUuid().createUuid()

log = settings.log

class Main(QMainWindow):
    '''This is the controlling class for the main gui'''
    def __init__(self):
        super(Main, self).__init__()
        # setup UI
        self.ui = Ui_MainWindow()
        self.ui.setupUi(self)
        self.setWindowTitle("PyFarm -- Version %s" % __VERSION__)
        self.modName = 'Main'

        # setup the working directory and default log output
        self.qdir = QDir()
        self.cwd = self.qdir.currentPath()
        self.sep = self.qdir.separator().toAscii()
        self.logOutDir = QDir("%s%slogs" % (self.cwd, self.sep))

        # if the directory does not exist, create it
        if not self.logOutDir.exists():
            log('PyFarm :: %s :: Creating output directory' % self.modName, 'standard')
            self.qdir.mkpath(self.logOutDir.path())

        self.ui.logDir.setText(self.logOutDir.path())
        self.connect(self.ui.browseForLogDir, SIGNAL("pressed()"), self.setNewLogDir)

        # update the installed avaliable software list
        for software in settings.installedSoftware():
            self.ui.softwareSelection.addItem(str(software))

        # setup data management
        self.dataJob = {}
        self.dataGeneral = GeneralManager(self.ui, __VERSION__)
        self.hostname = str(QHostInfo.localHostName())
        self.tableManager = JobTableManager(self)
        self.softwareManager = SoftwareContextManager(self)
        self.submitJob = SubmitManager(self)
        self.ip = str(QHostInfo.fromName(self.hostname).addresses()[0].toString())
        self.msg = MessageBox(self)
        setSoftware = self.softwareManager.setSoftware(self.ui.softwareSelection.currentText())

        if not setSoftware:
            msg = """<p>Could not find any software installed on system.
            This will not prevent you from launching PyFarm however you
            will be unable to render.<br><br><b>Options:</b>
            <ol>1.) Add remote hosts and use their installed software (not implimented yet)</ol>
            <ol>2.) Edit %s/settings.xml and add custom search paths</ol>
            <br><br>
            See the <a href="http://www.google.com">wiki</a> for more information.
            </p>
            """ % wd
            self.msg.warning("Software Not Installed", msg)

        # network server setup
        self.statusServer = StatusServer(self.dataJob, self.dataGeneral)
        self.connect(self.statusServer, SIGNAL('INIT'), self.initHost)
        self.connect(self.statusServer, SIGNAL('FRAME_COMPLETE'), self.frameComplete)
        self.logServer = UdpLoggerServer()
        self.connect(self.logServer, SIGNAL("incoming_line"), self.processLogLine)

        # start the servers
        if not self.statusServer.listen(QHostAddress('0.0.0.0'), settings.netPort('status')):
            log("PyFarm :: StatusServer :: Could not start the server: %s" % self.statusServer.errorString(), 'warning')
        else:
            log("PyFarm :: StatusServer :: Waiting for signals...", 'standard')

        # setup some basic colors
        self.red = QColor(255, 0, 0)
        self.green = QColor(0, 128, 0)
        self.blue = QColor(0, 0, 255)
        self.purple = QColor(128, 0, 128)
        self.white = QColor(255, 255, 255)
        self.yellow = QColor(255, 255, 0)
        self.black = QColor(0, 0, 0)
        self.orange = QColor(255, 165, 0)

        # inform the user of their settings
        self.updateStatus('SETTINGS', 'Got settings from settings.xml', 'purple')
        self.updateStatus('SETTINGS', 'Server IP: %s' % settings.netGeneral('host'), 'purple')
        self.updateStatus('SETTINGS', 'Broadcast Port: %s' % settings.netPort('broadcast'), 'purple')
        self.updateStatus('SETTINGS', 'StdOut Port: %s' % settings.netPort('stdout'), 'purple')
        self.updateStatus('SETTINGS', 'StdErr Port: %s' % settings.netPort('stderr'), 'purple')
        self.updateStatus('SETTINGS', 'Que Port: %s' % settings.netPort('que'), 'purple')

        for program in settings.installedSoftware():
            self.updateStatus('SETTINGS', '%s: %s' % (program, settings.command(program)), 'purple')

        # populate the avaliable list of renderers
        self.softwareManager.setSoftware(self.ui.softwareSelection.currentText())
        self.ui.softwareSelection.currentText()

        # setup ui vars
        self.ui.currentJobs.horizontalHeader().setStretchLastSection(True)

        # make signal connections
        ## ui signals
        self.connect(self.ui.enque, SIGNAL("pressed()"), self.submitJob.submitJob)
        self.connect(self.ui.render, SIGNAL("pressed()"), self.submitJob.startRender)
        self.connect(self.ui.softwareSelection, SIGNAL("currentIndexChanged(const QString&)"), self.softwareManager.setSoftware)

        # connect specific render option vars
        ## maya
        self.connect(self.ui.mayaBrowseForScene, SIGNAL("pressed()"),  self.softwareManager.browseForScene)
        self.connect(self.ui.mayaBrowseForOutputDir, SIGNAL("pressed()"), self.softwareManager.browseForMayaOutDir)
        self.connect(self.ui.mayaBrowseForProject, SIGNAL("pressed()"), self.softwareManager.browseForMayaProjectFile)
        self.connect(self.ui.mayaRenderLayers, SIGNAL("customContextMenuRequested(const QPoint &)"), self.mayaRenderLayersListMenu)
        self.connect(self.ui.mayaAddCamera, SIGNAL("clicked()"), self.mayaCameraEditMenu)

        ## houdini
        self.connect(self.ui.houdiniOutputCustomImageRes, SIGNAL("stateChanged(int)"), self.setHoudiniCustomResState)
        self.connect(self.ui.houdiniOutputKeepAspect, SIGNAL("stateChanged(int)"), self.setHoudiniKeepAspectRatio)
        self.connect(self.ui.houdiniOutputWidth, SIGNAL("valueChanged(int)"), self.setHoudiniWidth)
        self.connect(self.ui.houdiniOutputPixelAspect, SIGNAL("valueChanged(double)"), self.setHoudiniAspectRatio)
        self.connect(self.ui.houdiniBrowseForScene, SIGNAL("pressed()"), self.softwareManager.browseForScene)
        self.connect(self.ui.houdiniNodeList, SIGNAL("customContextMenuRequested(const QPoint &)"), self.houdiniNodeListMenu)

        # connect ui vars for
        ## network section signals
        self.connect(self.ui.findHosts, SIGNAL("pressed()"), self.findHosts)
        self.connect(self.ui.getHostInfo, SIGNAL("pressed()"), self.showHostInfo)
        self.connect(self.ui.addHost, SIGNAL("pressed()"), self._customHostDialog)
        self.connect(self.ui.removeHost, SIGNAL("pressed()"), self.dataGeneral.removeHost)

        self.fakeSetup()

    def setNewLogDir(self):
        '''Set a new output logging directory'''
        currentDir = self.logOutDir.path()
        outdir = QFileDialog.getExistingDirectory(\
            None,
            self.trUtf8("Please Select an Output Directory"),
            currentDir,
            QFileDialog.Options(QFileDialog.ShowDirsOnly))

        if outdir != '':
            self.ui.logDir.setText(outdir)
            self.logOutDir = QDir(outdir)

    def processLogLine(self, line):
        '''Process an incoming log line'''
        l = line[0].split("::")
        self.dataJob[l[0]].data.frame.writeLine(l[1], l[2], l[3], l[4])

    def frameComplete(self, job):
        '''Take action when a frame is finished'''
        logdir = self.logOutDir
        self.tableManager.frameComplete(job[0])
        self.submitJob.distribute.sendFrame(job[1])

################################
## BEGIN Context Menus
################################
    def globalPoint(self, widget, point):
        '''Return the global position for a given point'''
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
        '''Popup small menu to edit the camera list'''
        widget = self.ui.mayaAddCamera
        menu = QMenu()
        menu.addAction('Add Camera',self.mayaAddCamera)
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
## END Context Menu
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
                widget.setEnabbeginProcessingled(1)

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
## BEGIN Host Management
################################
    def _getHostSelection(self):
        '''
        Get the current host selection

        OUTPUT:
            list2 - [rowNum, [hostname, ipaddress, status]]
        '''
        output = []
        tmp = []
        output.append(list(self.ui.networkTable.selectedIndexes())[0].row())
        for i in list(self.ui.networkTable.selectedItems()):
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

    def initHost(self, info):
        '''Given the captured information, add it to self.generalData'''
        infosplit = info.split('||')
        sysinfo = infosplit[0].split(',')
        softwareList = infosplit[1:]

        # first process the system info
        for entry in sysinfo:
            try:
                key, value = entry.split('::')
                if str(key) == 'ip':
                    ip = str(value)
                elif str(key) == 'hostname':
                    hostname = str(value)
                elif str(key) == 'os':
                    os = str(value)
                elif str(key) == 'arch':
                    arch = str(value)
            except ValueError:
                pass

        softwareDict = {}
        for software in softwareList:
            version, location, generic = software.split('::')
            if str(generic) not in softwareDict:
                softwareDict[str(generic)] = {}
                softwareDict[str(generic)][str(version)] = str(location)
            else:
                softwareDict[str(generic)][str(version)]  = str(location)

        try:
            self.dataGeneral.addHost(ip, hostname, os, arch, softwareDict)
        except UnboundLocalError:
            pass

    def showHostInfo(self):
        '''Show some info about the host'''
        widget = HostInfo()

        # gather information
        row = self.ui.networkTable.currentRow()
        host = self.dataGeneral.network.host

        try:
            ip = str(self.ui.networkTable.item(row, 0).text())
        except AttributeError:
            self.msg.info("Please select a host first.", "Before viewing information about a host you must first select one from the network table list.")

        # apply it to the widgets
        widget.ui.ipAddress.setText(ip)
        widget.ui.hostname.setText(host.hostname(ip))
        widget.ui.status.setText(host.status(ip, text=True))
        widget.ui.os.setText(host.os(ip))
        widget.ui.architecture.setText(host.architecture(ip))
        widget.ui.rendered.setText(host.rendered(ip, string=True))
        widget.ui.failed.setText(host.failed(ip, string=True))
        widget.ui.failureRate.setText(host.failureRate(ip, string=True))

        # add the software the the tree widget
        software = host.softwareDict(ip)
        softwareTree = widget.ui.softwareTree
        for key in software.keys():
            item = QTreeWidgetItem()
            item.setText(0, QString(key))
            for entry in host.installedVersions(ip, key):
                entryItem = QTreeWidgetItem()
                try:
                    entryItem.setText(0, QString(entry.split(' ')[1]))
                # unless we can't split the name
                except IndexError:
                    entryItem.setText(0, QString(entry))
                item.addChild(entryItem)
            softwareTree.addTopLevelItem(item)

        # open the widget
        widget.exec_()
################################
## END Host Management
################################
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
        findHosts = BroadcastSender(__UUID__, self)
        progress = ProgressDialog(QString("Network Broadcast Progress"), \
                                                            QString("Cancel Broadcast"), \
                                                            0, settings.broadcastValue('maxCount'), self)

        self.connect(progress, SIGNAL("canceled()"), findHosts.quit)
        self.connect(findHosts, SIGNAL("next"), progress.next)
        self.connect(findHosts, SIGNAL("done"), self.dataGeneral.uiStatus.pyfarm.setNetwork)
        self.dataGeneral.uiStatus.pyfarm.setMaster(1)
        self.dataGeneral.uiStatus.pyfarm.setNetwork(1)
        progress.show()
        findHosts.run()

################################
## END Job/Que System
################################
    def updateStatus(self, section, msg, color='black'):
        '''
        Update the ui's status window

        VARS:
            section (string)-- The section to report from (ex. NETWORK)
            msg (string) - The message to post
            color (string) - The color name or hex value to set the section
        '''
        self.ui.status.append('<font color=%s><b>%s</b></font> - %s' % (color, section, msg))

    def fakeSetup(self):
        '''Setup the fake information for presentation'''
        from lib.ui.main.maya.RenderLayers import MayaCamAndLayers
        self.ui.inputJobName.setText('TestJobA')
        getCamAndLayers = MayaCamAndLayers(self.ui.mayaRenderLayers, self.ui.mayaCamera)
        # for home
        try:
            getCamAndLayers.run('/farm/projects/PyFarm/trunk/tests/maya/2009/scenes/01_mr_renderLayers.ma')
            self.ui.mayaScene.setText('/farm/projects/PyFarm/trunk/tests/maya/2009/scenes/01_mr_renderLayers.ma')
        # for monty
        except IOError:
            getCamAndLayers.run('/stuhome/01_mr_renderLayers.ma')
            self.ui.mayaScene.setText('/stuhome/01_mr_renderLayers.ma')
#    self.ui.inputJobName.setText('TestJobA')
#    self.submitJob.submitJob()
#    self.ui.inputJobName.setText('TestJobB')
#    self.submitJob.submitJob()

    def closeEvent(self, event):
        '''Run when closing the main gui, used to "cleanup" the program state'''
        # if we have connected hosts
        hostCount = len(self.dataGeneral.network.hostList())
        if hostCount:
            closeEventManager = CloseEventManager(self.dataGeneral, self)
            exit_dialog = closeEventManager.hostExitDialog()

            if exit_dialog == QMessageBox.Yes:
                log("PyFarm :: Main.closeEvent :: Shutting Down Clients...", 'standard')
                closeEventManager.shutdownHosts()

            elif exit_dialog == QMessageBox.No:
                log("PyFarm :: Main.closeEvent :: Restarting clients...", 'standard')
                closeEventManager.restartHosts()

            elif exit_dialog == QMessageBox.Help:
                log("PyFarm :: Main.closeEvent :: Presenting host help", 'debug')
                closeEventManager.exitHelp()
                self.closeEvent(self)
        else:
            log("PyFarm :: Main.closeEvent :: No hosts to shutdown", 'warning')
################################
## END General Utilities
################################

# setup and run the event loop
app = QApplication(sys.argv)
main = Main()
main.show()
sys.exit(app.exec_())
