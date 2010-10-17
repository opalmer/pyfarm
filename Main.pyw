#!/usr/bin/env python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 12 2009
PURPOSE: Main program to run and manage PyFarm

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

import os
import sys
import unittest

DEVELOPER = 'Oliver Palmer'
HOMEPAGE  = 'http://www.pyfarm.net'
VERSION   = '0.5.0'
MODULE    = 'Main.pyw'
LOGLEVEL  = 2
ROOT      = os.path.dirname(os.path.abspath(__file__))
CFG_ROOT  = os.path.join(ROOT, "cfg")
ICN_ROOT  = os.path.join(ROOT, "icons")
QUE_ICN   = os.path.join(ICN_ROOT, "queue")
CFG_GEN   = os.path.join(CFG_ROOT, "general.ini")

# From PyQt
from PyQt4.Qt import Qt
from PyQt4 import QtCore, QtGui, QtNetwork, uic

# From PyFarm
import cfg.resources_rc
from lib.ui import Dialogs
from lib.Slots import Slots
from lib.Logger import Logger
from lib.Settings import ReadConfig
from lib.system.Info import SystemInfo
from lib.net.tcp.Queue import QueueServer
from lib.net.udp.Broadcast import BroadcastSender

# sestup logging
log = Logger(MODULE, LOGLEVEL)

class MainWindow(QtGui.QMainWindow):
    '''This is the controlling class for the main gui'''
    def __init__(self):
        super(MainWindow, self).__init__()
        self.ui = uic.loadUi(
                                os.path.join(
                                                ROOT,
                                                "lib",
                                                "ui",
                                                "MainWindow.ui"
                                            ),
                                            baseinstance=self
                            )

        # setup layouts
        self.setWindowTitle("PyFarm -- Version %s" % VERSION)
        self.centralWidget().setLayout(self.ui.layoutRoot)
        self.ui.toolboxNetwork.setLayout(self.ui.layoutNetwork)
        self.ui.toolboxSubmit.setLayout(self.ui.submitToolboxLayout)
        self.ui.toolboxJobs.setLayout(self.ui.jobsToolboxLayout)
        self.ui.hostControls.setLayout(self.ui.layoutHostControlButtons)
        self.ui.statusDock.widget().setLayout(self.ui.rootDockLayout)
        self.ui.statusDock.setWindowTitle("Status Console")

        # add menu to submit button
        self.submitMenu = QtGui.QMenu()
        self.submitMenu.addAction("Run")
        self.submitMenu.addAction("On Hold")
        self.submitMenu.addAction("Waiting On Job")
        self.submit.setMenu(self.submitMenu)
        self.connect(
                        self.submitMenu,
                        QtCore.SIGNAL("triggered(QAction *)"),
                        self.submitAction
                    )
        # END submit menu

        # general setup and variables
        self.isClosing = False
        self.config    = ReadConfig(CFG_GEN)
        self.slots     = Slots(self, self.config)
        self.runServers()



    def runServers(self):
        '''Run the background servers required to operate PyFarm'''
        self.queueServer = QueueServer()
        if not self.queueServer.listen(QtNetwork.QHostAddress.Any, self.config['servers']['queue']):
            log.fatal("Could not start the queue server: %s" % self.queueServer.errorString())

    def foundNewClient(self, data):
        print data

    # MainWindow slots, actions, and processes
    # Other actions could include:
    ## slots.stats
    ## slots.state (current software, job, crons, etc.)
    def submitAction(self, action):
        '''Return a QIcon object with icon preloaded'''
        log.ui("Submitted With: %s" % action.text())

    def hostFindPressed(self): self.slots.host.find()
    def hostAddPressed(self): pass # self.slots.host.add()
    def hostInfoPressed(self): pass # self.slots.host.info()
    def hostDisablePressed(self): pass # self.slots.host.disable()
    def hostEditPressed(self): pass # self.slots.host.edit()
    def hostRemovePressed(self): pass # self.slots.host.remove()
    def aboutTriggered(self): pass # self.slots.help.about()
    def diagnosticsTriggered(self): pass # self.slots.help.diagnostics()
    def documentationTriggered(self): pass # self.slots.help.about()
    def bugsTriggered(self): pass # self.slots.help.about()
    def updatesTriggered(self): pass # self.slots.help.about()
    def queueLoadTriggered(self): pass # RUN ACTION HERE
    def queueSaveTriggered(self): pass # RUN ACTION HERE
    def quitTriggered(self): self.closeEvent('manual')

    def closeEventHandler(self, exitAnswers):
        '''
        Used to handle the final user choices reguarding
        database, clients, etc.
        '''
        for key, value in exitAnswers.items():
            log.ui("Exit State Choice: %s -> %s" % (key, str(value)))

        log.critical("Closing!")
        sys.exit()

    def closeEvent(self, event):
        '''When the ui is attempting to exit, run this first.  However, make sure we only do this once'''
        exit = Dialogs.CloseEvent()
        self.connect(exit, QtCore.SIGNAL("state"), self.closeEventHandler)
        exit.exec_()

        if self.isClosing:
            self.close()
        else:
            self.isClosing = True
            print "What would you like to do?: Save Job Database, Interface Settings,  Shutdown Remote Client Program"
            if event is 'manual': self.close()

#
#        # update the installed avaliable software list
#        for software in settings.installedSoftware():
#            self.ui.softwareSelection.addItem(str(software))
#
#        # setup data management
#        self.dataJob = {}
#        self.dataGeneral = GeneralManager(self.ui, VERSION)
#        self.hostname = str(QHostInfo.localHostName())
#        self.tableManager = JobTableManager(self)
#        self.softwareManager = SoftwareContextManager(self)
#        self.submitJob = SubmitManager(self)
#        self.ip = str(QHostInfo.fromName(self.hostname).addresses()[0].toString())
#        self.msg = MessageBox(self)
#        setSoftware = self.softwareManager.setSoftware(self.ui.softwareSelection.currentText())
#
#        # if we cannot find any software installed
#        if not setSoftware:
#            msg = """<p>Could not find any software installed on system.
#            This will not prevent you from launching PyFarm however you
#            will be unable to render.<br><br><b>Options:</b>
#            <ol>1.) Add remote hosts and use their installed software (not implimented yet)</ol>
#            <ol>2.) Edit %s/settings.xml and add custom search paths</ol>
#            <br><br>
#            See the <a href="http://www.google.com">wiki</a> for more information.
#            </p>
#            """ % wd
#            self.msg.warning("Software Not Installed", msg)
#

#
#        # setup some basic colors
#        self.red = QColor(255, 0, 0)
#        self.green = QColor(0, 128, 0)
#        self.blue = QColor(0, 0, 255)
#        self.purple = QColor(128, 0, 128)
#        self.white = QColor(255, 255, 255)
#        self.yellow = QColor(255, 255, 0)
#        self.black = QColor(0, 0, 0)
#        self.orange = QColor(255, 165, 0)
#
#        # inform the user of their settings
        #self.updateStatus('SETTINGS', 'Got settings from ini file', 'purple')
#        self.updateStatus('SETTINGS', 'Server IP: %s' % self.network.ip(), 'purple')
#        self.updateStatus('SETTINGS', 'Broadcast Port: %s' % self.config.servers['broadcast'], 'purple')
#        self.updateStatus('SETTINGS', 'Logging Port: %s' % self.config.servers['logging'], 'purple')
#        self.updateStatus('SETTINGS', 'Queue Port: %s' %self.config.servers['queue'], 'purple')
#
#        for program in settings.installedSoftware():
#            self.updateStatus('SETTINGS', '%s: %s' % (program, settings.command(program)), 'purple')
#
#        # populate the avaliable list of renderers
#        self.softwareManager.setSoftware(self.ui.softwareSelection.currentText())
#        self.ui.softwareSelection.currentText()
#
#        # setup ui vars
#        self.ui.currentJobs.horizontalHeader().setStretchLastSection(True)
#
#        # make signal connections
#        ## ui signals
#        self.connect(self.ui.enque, SIGNAL("pressed()"), self.submitJob.submitJob)
#        self.connect(self.ui.render, SIGNAL("pressed()"), self.submitJob.startRender)
#        self.connect(self.ui.softwareSelection, SIGNAL("currentIndexChanged(const QString&)"), self.softwareManager.setSoftware)
#
#        # connect specific render option vars
#        ## maya
#        self.connect(self.ui.mayaBrowseForScene, SIGNAL("pressed()"),  self.softwareManager.browseForScene)
#        self.connect(self.ui.mayaBrowseForOutputDir, SIGNAL("pressed()"), self.softwareManager.browseForMayaOutDir)
#        self.connect(self.ui.mayaBrowseForProject, SIGNAL("pressed()"), self.softwareManager.browseForMayaProjectFile)
#        self.connect(self.ui.mayaRenderLayers, SIGNAL("customContextMenuRequested(const QPoint &)"), self.mayaRenderLayersListMenu)
#        self.connect(self.ui.mayaAddCamera, SIGNAL("clicked()"), self.mayaCameraEditMenu)
#
#        ## houdini
#        self.connect(self.ui.houdiniOutputCustomImageRes, SIGNAL("stateChanged(int)"), self.setHoudiniCustomResState)
#        self.connect(self.ui.houdiniOutputKeepAspect, SIGNAL("stateChanged(int)"), self.setHoudiniKeepAspectRatio)
#        self.connect(self.ui.houdiniOutputWidth, SIGNAL("valueChanged(int)"), self.setHoudiniWidth)
#        self.connect(self.ui.houdiniOutputPixelAspect, SIGNAL("valueChanged(double)"), self.setHoudiniAspectRatio)
#        self.connect(self.ui.houdiniBrowseForScene, SIGNAL("pressed()"), self.softwareManager.browseForScene)
#        self.connect(self.ui.houdiniNodeList, SIGNAL("customContextMenuRequested(const QPoint &)"), self.houdiniNodeListMenu)

    def foundHost(self, host):
        '''When a host is found, run the appropriate actions'''
        log.netclient("Found host: %s" % host)

    def searchStarted(self):
        print "Running"

    def findHosts(self):
        '''Get hosts via broadcast packet, add them to self.hosts'''
        # 1) Start timer
        # 2) Run broadcast
        # 3) Kill broadcast after timer expires
        self.broadcast = BroadcastSender(self.config)
        self.connect(self.broadcast, SIGNAL("client-address"), self.foundHost)
        self.connect(self.broadcast, SIGNAL("started()"), self.searchStarted)
        self.broadcast.run()

#################################
### BEGIN Context Menus
#################################
#    def globalPoint(self, widget, point):
#        '''Return the global position for a given point'''
#        return widget.mapToGlobal(point)
#
#    def CreateContextMenus(self):
#        '''
#        Create the custom context menus in advance so
#        they will not need to be created later.
#        '''
#        # create a context menu for the job table
#        jobContext = QMenu()
#        jobContext.addAction('Hello')
#        self.jobContextMenu = jobContext
#
#    def currentJobsContextMenu(self, pos):
#        menu = QMenu()
#        menu.addAction('Job Details', self.JobDetails)
#        menu.addAction('Remove Job', self.RemoveJobFromTable)
#        menu.exec_(self.globalPoint(self.ui.currentJobs, pos))
#
#    def houdiniNodeListMenu(self, pos):
#        '''
#        Popup a custom context menu for the houdini
#        node list
#        '''
#        menu = QMenu()
#        menu.addAction('Add Node', self.houdiniAddOutputNode)
#        menu.addAction('Remove Node', self.houdiniNodeListRemoveSelected)
#        menu.addAction('Empty List', self.houdiniNodeListEmpty)
#        menu.exec_(self.globalPoint(self.ui.houdiniNodeList, pos))
#
#    def mayaCameraEditMenu(self):
#        '''Popup small menu to edit the camera list'''
#        widget = self.ui.mayaAddCamera
#        menu = QMenu()
#        menu.addAction('Add Camera',self.mayaAddCamera)
#        menu.addAction('Remove Camera', self.mayaRemoveCamera)
#        menu.addAction('Empty List', self.ui.mayaCamera.clear)
#        menu.exec_(self.globalPoint(widget, -widget.mapToParent(-widget.pos())))
#
#    def mayaRenderLayersListMenu(self, pos):
#        '''
#        Popup a custom context menu for the maya render
#        layer list
#        '''
#        menu = QMenu()
#        menu.addAction('Add Layer', self.mayaAddLayer)
#        menu.addAction('Remove Layer', self.mayaRenderLayerRemove)
#        menu.addAction('Empty List', self.mayaRenderLayerEmpty)
#        menu.exec_(self.globalPoint(self.ui.mayaRenderLayers, pos))
#
##############################
### END Context Menu
### BEGIN Maya Settings
#################################
#    def mayaRemoveCamera(self):
#        '''
#        Remove the currently selected camera
#        '''
#        widget = self.ui.mayaCamera
#        widget.removeItem(widget.currentIndex())
#
#    def mayaAddCamera(self):
#        '''
#        Add a custom camera to maya
#        '''
#        dialog = CustomObjectDialog(self)
#        self.connect(dialog, SIGNAL("objectName"), self.ui.mayaCamera.addItem)
#        dialog.exec_()
#
#    def mayaAddLayer(self):
#        '''
#        Add a custom layer to maya
#        '''
#        dialog = CustomObjectDialog(self)
#        self.connect(dialog, SIGNAL("objectName"), self.ui.mayaRenderLayers.addItem)
#        dialog.exec_()
#
#    def mayaEmptyLayersAndCameras(self):
#        '''
#        Empty render layer list and the camera list
#        '''
#        self.ui.mayaRenderLayers.clear()
#        self.ui.mayaCamera.clear()
#
#    def mayaRenderLayerRemove(self):
#        '''
#        Remove the currently selected layer(s) from
#        the node list.
#        '''
#        for item in self.ui.mayaRenderLayers.selectedItems():
#            item.setHidden(1)
#
#    def mayaRenderLayerEmpty(self):
#        '''
#        Clear all nodes from the node list
#        '''
#        self.ui.mayaRenderLayers.clear()
#
#################################
### END Maya Settings
#################################
### BEGIN Houdini Settings
#################################
#    def houdiniAddOutputNode(self):
#        '''
#        Add an output node too the node list
#        '''
#        dialog = CustomObjectDialog(self)
#        self.connect(dialog, SIGNAL("objectName"), self.ui.houdiniNodeList.addItem)
#        dialog.exec_()
#
#    def houdiniNodeListRemoveSelected(self):
#        '''
#        Remove the currently selected node(s) from
#        the node list.
#        '''
#        for item in self.ui.houdiniNodeList.selectedItems():
#            item.setHidden(1)
#
#    def houdiniNodeListEmpty(self):
#        '''
#        Clear all nodes from the node list
#        '''
#        self.ui.houdiniNodeList.clear()
#
#    def setHoudiniScene(self):
#        '''
#        Display a search gui then set the houdini scene.
#        Lastly, gather all of the output nodes from the file.
#        '''
#        projectFile = QFileDialog.getOpenFileName(\
#            None,
#            self.trUtf8("Select Your Houdini Scene"),
#            QString(),
#            self.trUtf8(self.fileGrep),
#            None,
#            QFileDialog.Options(QFileDialog.DontResolveSymlinks))
#
#        if projectFile != '':
#            self.ui.houdiniFile.setText(projectFile)
#            self.houdiniFindOutputNodes(projectFile)
#
#    def houdiniFindOutputNodes(self, inFile):
#        '''
#        Given an input file, find all of the output nodes and
#        display them in the node table.
#        '''
#        hou = open(inFile)
#        exp = QRegExp(r"""[0-9]+out/[0-9a-zA-Z]+[.]parm""")
#
#        for line in hou.readlines():
#            if not exp.indexIn(line):
#                self.ui.houdiniNodeList.addItem(QString(line.split('/')[1].split('.')[0]))
#
#    def setHoudiniCustomResState(self, newState):
#        '''
#        Based on user pref, setup the ui for custom image resolution
#        '''
#        widgets = [\
#                    self.ui.houdiniOutputKeepAspect,
#                    self.ui.houdiniOutputWidth,
#                    self.ui.houdiniOutputWidthLabel,
#                    self.ui.houdiniOutputPixelAspect,
#                    self.ui.houdiniOutputPixelAspectLabel]
#
#        if newState:
#            for widget in widgets:
#                widget.setEnabled(0)
#
#            self.ui.houdiniOutputHeight.setEnabled(0)
#            self.ui.houdiniOutputHeightLabel.setEnabled(0)
#
#        else:
#            for widget in widgets:
#                widget.setEnabbeginProcessingled(1)
#
#            if self.houdiniKeepAspectRatio():
#                self.ui.houdiniOutputHeight.setEnabled(0)
#                self.ui.houdiniOutputHeightLabel.setEnabled(0)
#            else:
#                self.ui.houdiniOutputHeight.setEnabled(1)
#                self.ui.houdiniOutputHeightLabel.setEnabled(1)
#
#    def setHoudiniKeepAspectRatio(self, newState):
#        '''
#        Given newState set the global var accordingly and
#        enable/disable the appropriate combo box.
#        '''
#        # if the box is checked, keep the aspect ratio
#        if newState:
#            self.ui.houdiniOutputHeight.setEnabled(0)
#            self.ui.houdiniOutputHeightLabel.setEnabled(0)
#        else:
#            self.ui.houdiniOutputHeight.setEnabled(1)
#            self.ui.houdiniOutputHeightLabel.setEnabled(1)
#
#    def setHoudiniWidth(self, width):
#        '''
#        Set the width of the output image
#        '''
#        if not self.houdiniKeepAspectRatio():
#            pass
#        else:
#            a = float(width)
#            b = float(self.houdiniAspectRatio())
#            self.ui.houdiniOutputHeight.setValue(a//b)
#
#    def setHoudiniHeight(self, height):
#        '''
#        Set the height of the output image
#        '''
#        try:
#            if not self.houdiniKeepAspect:
#                pass
#        except AttributeError:
#            self.houdiniKeepAspect = 1
#
#    def setHoudiniAspectRatio(self, aspectRatio):
#        '''
#        Set the aspect ratio of the render
#        '''
#        self.setHoudiniWidth(self.ui.houdiniOutputWidth.value())
#
#    def houdiniAspectRatio(self):
#        '''
#        Return the current aspect ratio from
#        the interface.
#        '''
#        return self.ui.houdiniOutputPixelAspect.value()
#
#    def houdiniKeepAspectRatio(self):
#        '''
#        Return the state of the keep aspect ratio check
#        box.
#        '''
#        return self.ui.houdiniOutputKeepAspect.isChecked()
#
#################################
### END Houdini Settings
### BEGIN Host Management
#################################
#    def _getHostSelection(self):
#        '''
#        Get the current host selection
#
#        OUTPUT:
#            list2 - [rowNum, [hostname, ipaddress, status]]
#        '''
#        output = []
#        tmp = []
#        output.append(list(self.ui.networkTable.selectedIndexes())[0].row())
#        for i in list(self.ui.networkTable.selectedItems()):
#            tmp.append(str(i.text()))
#
#        output.append(tmp)
#
#        return output
#
#    def _customHostDialog(self):
#        ''''Open a dialog to add a custom host'''
#        self.customHostDialog = AddHostDialog()
#        self.connect(self.customHostDialog.buttons, SIGNAL("accepted()"), self._addCustomHost)
#        self.customHostDialog.exec_()
#
#    def _addCustomHost(self):
#        '''Add the custom host to self.hosts and the gui, close the dialog'''
#        host = self.customHostDialog.inputHost.text()
#        self.addHost(str(host))
#        self.customHostDialog.close()
#
#    def initHost(self, info):
#        '''Given the captured information, add it to self.generalData'''
#        infosplit = info.split('||')
#        sysinfo = infosplit[0].split(',')
#        softwareList = infosplit[1:]
#
#        # first process the system info
#        for entry in sysinfo:
#            try:
#                key, value = entry.split('::')
#                if str(key) == 'ip':
#                    ip = str(value)
#                elif str(key) == 'hostname':
#                    hostname = str(value)
#                elif str(key) == 'os':
#                    os = str(value)
#                elif str(key) == 'arch':
#                    arch = str(value)
#            except ValueError:
#                pass
#
#        softwareDict = {}
#        for software in softwareList:
#            version, location, generic = software.split('::')
#            if str(generic) not in softwareDict:
#                softwareDict[str(generic)] = {}
#                softwareDict[str(generic)][str(version)] = str(location)
#            else:
#                softwareDict[str(generic)][str(version)]  = str(location)
#
#        try:
#            self.dataGeneral.addHost(ip, hostname, os, arch, softwareDict)
#        except UnboundLocalError:
#            pass
#
#    def showHostInfo(self):
#        '''Show some info about the host'''
#        widget = HostInfo()
#
#        # gather information
#        row = self.ui.networkTable.currentRow()
#        host = self.dataGeneral.network.host
#
#        try:
#            ip = str(self.ui.networkTable.item(row, 0).text())
#        except AttributeError:
#            self.msg.info("Please select a host first.", "Before viewing information about a host you must first select one from the network table list.")
#
#        # apply it to the widgets
#        widget.ui.ipAddress.setText(ip)
#        widget.ui.hostname.setText(host.hostname(ip))
#        widget.ui.status.setText(host.status(ip, text=True))
#        widget.ui.os.setText(host.os(ip))
#        widget.ui.architecture.setText(host.architecture(ip))
#        widget.ui.rendered.setText(host.rendered(ip, string=True))
#        widget.ui.failed.setText(host.failed(ip, string=True))
#        widget.ui.failureRate.setText(host.failureRate(ip, string=True))
#
#        # add the software the the tree widget
#        software = host.softwareDict(ip)
#        softwareTree = widget.ui.softwareTree
#        for key in software.keys():
#            item = QTreeWidgetItem()
#            item.setText(0, QString(key))
#            for entry in host.installedVersions(ip, key):
#                entryItem = QTreeWidgetItem()
#                try:
#                    entryItem.setText(0, QString(entry.split(' ')[1]))
#                # unless we can't split the name
#                except IndexError:
#                    entryItem.setText(0, QString(entry))
#                item.addChild(entryItem)
#            softwareTree.addTopLevelItem(item)
#
#        # open the widget
#        widget.exec_()
#################################
### END Host Management
#################################
#    def workSent(self, work):
#        '''Inform the user that a job is being sent'''
#        self.updateStatus('NETWORK', 'Sending frame %s from job %s to %s' % (work[2], work[1], work[0]), 'green')
#
#    def workComplete(self, worker):
#        '''Inform the user of done frames'''
#        ip = worker[0]
#        job = worker[1]
#        frame = worker[2]
#        self.updateStatus('QUEUE', '%s completed frame %s of job %s' % (ip, frame, job), 'brown')
#
#    def killRender(self):
#        '''Kill the current job'''
#        self.ui.cancelRender.setEnabled(False)
#        self.que.emptyQue()
#        # send the kill job to clients !
#        self.updateStatus('QUEUE', '%s frames waiting to render' % self.que.size(), 'brown')
#        self.ui.render.setEnabled(True)
#
#        self.updateStatus('NETWORK', 'Searching for hosts...', 'green')
#        findHosts = BroadcastSender(__UUID__, self)
#        progress = ProgressDialog(QString("Network Broadcast Progress"), \
#                                                            QString("Cancel Broadcast"), \
#                                                            0, settings.broadcastValue('maxCount'), self)
#
#        self.connect(progress, SIGNAL("canceled()"), findHosts.quit)
#        self.connect(findHosts, SIGNAL("next"), progress.next)
#        self.connect(findHosts, SIGNAL("done"), self.dataGeneral.uiStatus.pyfarm.setNetwork)
#        self.dataGeneral.uiStatus.pyfarm.setMaster(1)
#        self.dataGeneral.uiStatus.pyfarm.setNetwork(1)
#        progress.show()
#        findHosts.run()
#
#################################
### END Job/Que System
#################################
    def updateStatus(self, section, msg, color='black'):
        '''
        Update the ui's status window

        VARS:
            section (string)-- The section to report from (ex. NETWORK)
            msg (string) - The message to post
            color (string) - The color name or hex value to set the section
        '''
        self.ui.status.append('<font color=%s><b>%s</b></font> - %s' % (color, section, msg))
#
#    def closeEvent(self, event):
#        '''Run when closing the main gui, used to "cleanup" the program state'''
#        # if we have connected hosts
#        hostCount = len(self.dataGeneral.network.hostList())
#        if hostCount:
#            closeEventManager = CloseEventManager(self.dataGeneral, self)
#            exit_dialog = closeEventManager.hostExitDialog()
#
#            if exit_dialog == QMessageBox.Yes:
#                log.info("Shuttdown down clients!")
#                closeEventManager.shutdownHosts()
#
#            elif exit_dialog == QMessageBox.No:
#                log.info("Restarting clients!")
#                closeEventManager.restartHosts()
#
#            elif exit_dialog == QMessageBox.Help:
#                log.ui("closeEvent() - Presenting host help")
#                closeEventManager.exitHelp()
#                self.closeEvent(self)
#        else:
#            log.network("closeEvent() - No hosts to shutdown")
#################################
### END General Utilities
#################################

class Testing(QtCore.QObject):
    '''Quick testing code'''
    def __init__(self, parent=None):
        super(Testing, self).__init__(parent)
        log = Logger("Main.Testing")
        self.config = ReadConfig(CFG_ROOT)
        log.debug("Test code initilized")

    def broadIncriment(self):
        log.netclient("Incrimented")

    def broadDone(self, signal):
        log.netclient("Broadcast complete")

    def sendBroadcast(self):
        '''Send out a broadcast to inform clients of the master node'''
        broadcast = BroadcastSender(self.config, self)
        #self.connect(broadcast, SIGNAL("next"), self.broadIncriment)
        #self.connect(broadcast, SIGNAL("done"), self.broadDone)
        broadcast.run()

    def runStatusServer(self):
        '''Run the status server and listen for connections'''
        log.netserver("Running status server")

    def run(self, option=None, opt=None, value=None, parser=None):
        log.debug("Running test code")

        self.runStatusServer()
        self.sendBroadcast()

        log.debug("Test run complete")
        log.terminate("Testing Terminated")

if __name__ != '__MAIN__':
    # command line opton parsing
    import lib.InputFlags as flags
    from optparse import OptionParser
    about   = flags.About(DEVELOPER, 'GNU-LGPL_Header.txt')
    sysinfo = SystemInfo(os.path.join(CFG_ROOT, "general.ini"))

    ###############################
    # Command Line Options
    ###############################

    parser = OptionParser(version="PyFarm v%s" % VERSION)
    parser.add_option(
                        "--testing", dest="testing",
                        default=False, action="store_true",
                        help="Instead of running the ui, run a small set of tests"
                     )
    parser.add_option(
                        "--author", dest="author", action="callback",
                        callback=about.author, help="Return the developer's name"
                     )
    parser.add_option(
                        "--license", dest="license", action="callback",
                        callback=about.license, help="Get the LGPL license header"
                      )
    parser.add_option(
                        "--no-version-check", dest="versionCheck", action="store_false",
                        default=True, help="Bypass software unittesting for Python and PyQt"
                      )
    #parser.add_option("--sysinfo", dest="sysinfo", action="callback",
                        #callback=sysinfo.hardware, help="Get processor, ram, etc. info")
    #parser.add_option("--clean", action="callback",  callback=sysutil.clean,
                        #help="remove all byte-compiled Python files")
    #parser.add_option("--test", action="callback", callback=test.run,
                        #help="run testing code")
    #parser.add_option("-d", "--db", action="callback", callback=SQLio.InitDb,
                        #help="Set the database for PyFarm before starting the ui")
    (options, args) = parser.parse_args()
    sys.exit("FATAL: Testing Mode")

    if options.testing:
        print "============================"
        log.warning("Entering testing mode")
        test = Testing()
        test.run()

    else:
        ###############################
        # Event Loop Start
        ###############################
        log.debug("PID: %s" % os.getpid())
        app    = QtGui.QApplication(sys.argv)

        align  = Qt.AlignBottom
        pixmap = QtGui.QPixmap(os.path.join(ICN_ROOT, "splash.png"))
        splash = QtGui.QSplashScreen(pixmap)
        splash.show()

        ###############################
        # Unit Tests, for safety!
        ###############################
        testVerbosity = 2

        if options.versionCheck:
            from lib.test import ModuleImports
            msg = "Running Unit Test: Version and Module Check"
            splash.showMessage(msg, align)
            log.info(msg)
            test = unittest.TestLoader().loadTestsFromTestCase(ModuleImports.ModuleTests)
            unittest.TextTestRunner(verbosity=testVerbosity).run(test)

        from lib.test import ValidateLogging
        msg = "Running Unit Test: Logging"
        splash.showMessage(msg, align)
        log.info(msg)
        test = unittest.TestLoader().loadTestsFromTestCase(ValidateLogging.Validate)
        unittest.TextTestRunner(verbosity=testVerbosity).run(test)

        from lib.test import ValidateNetConfig
        msg = "Running Unit Test: Network Configuration"
        splash.showMessage(msg, align)
        log.info(msg)
        test = unittest.TestLoader().loadTestsFromTestCase(ValidateNetConfig.Validate)
        unittest.TextTestRunner(verbosity=testVerbosity).run(test)

        splash.close()

        ###############################
        # Run UI
        ###############################
        main = MainWindow()
        main.show()
        sys.exit(app.exec_())

else:
    log.fatal("This program is not meant to be imported!")
