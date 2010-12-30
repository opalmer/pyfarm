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

from PyQt4.Qt import Qt
from PyQt4 import QtCore, QtGui, QtNetwork, uic

CWD       = os.path.dirname(os.path.abspath(__file__))
PYFARM    = CWD
MODULE    = os.path.basename(__file__)
CONTEXT   = MODULE.split(".")[0]
CFG_ROOT  = os.path.join(PYFARM, "cfg")
ICN_ROOT  = os.path.join(PYFARM, "icons")
QUE_ICN   = os.path.join(ICN_ROOT, "queue")
CFG_GEN   = os.path.join(CFG_ROOT, "general.ini")
DEVELOPER = 'Oliver Palmer'
HOMEPAGE  = 'http://www.pyfarm.net'
VERSION   = '0.5.0'
LOGLEVEL  = 2
DEBUG     = True
UNITTESTS = False

import lib.net
import cfg.resources_rc
from lib.net import tcp, udp
from lib import db, Logger, ui, Slots, Settings, Session, system

# sestup logging
log = Logger.Logger(MODULE, LOGLEVEL)
SQL = db.connect()

class MainWindow(QtGui.QMainWindow):
    '''This is the controlling class for the main gui'''
    def __init__(self):
        super(MainWindow, self).__init__()
        self.pidFile     = Session.State(context=CONTEXT)
        self.closeForced = False

        if self.pidFile.running() or self.pidFile.exists():
            self.handlePid()

        else:
            self.pidFile.write()

        # load the ui file
        self.ui = uic.loadUi(
                                os.path.join(
                                                PYFARM,
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
        # END layout

        # setup sqllite tables
        netTable        = self.ui.networkTable
        SqlTable        = ui.SqlTables
        columns         = ("hostname", "ip", "status")
        sortCol         = "hostname"
        self.hostTable  = SqlTable.Manager(SQL, netTable, "hosts", columns, sort=sortCol)

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

        self.connect(
                        self.ui.hostRefresh,
                        QtCore.SIGNAL("pressed()"),
                        self.refreshHosts
                    )

        # general setup and variables
        self.isClosing = False
        self.config    = Settings.ReadConfig.general(CFG_GEN)
        self.slots     = Slots.Slots(self, self.config)
        self.runServers()

    def refreshHosts(self):
        self.hostTable.refresh()

    def handlePid(self):
        '''Handle actions relating to the process id file'''
        title  = "%s Is Already Running" % CONTEXT
        msg    = "PyFarm already seems to be open, terminate the running"
        msg   += " process if needed and continue?"
        log.warning("%s: User input required" % title)
        yes    = QtGui.QMessageBox.Yes
        no     = QtGui.QMessageBox.No
        msgBox = QtGui.QMessageBox.warning(
                                            self, title, msg,
                                            yes|no
                                          )

        if msgBox == yes:
            self.pidFile.write(force=True)

        else:
            log.warning("Not overwriting PID file")

    def runServers(self):
        '''Run the background servers required to operate PyFarm'''
        self.queueServer = tcp.Queue.QueueServer(main=self)

        if not self.queueServer.listen(QtNetwork.QHostAddress.Any, self.config['servers']['queue']):
            error = "Could not start the queue server: %s" % self.queueServer.errorString()
            log.fatal(error)

            if not DEBUG: raise lib.net.ServerFault(error)
            else: log.warning("Bypassing exception!!!")

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
        if not self.closeForced:
        #    for key, value in exitAnswers.items():
         #       log.ui("Exit State Choice: %s -> %s" % (key, str(value)))

            log.info("Removing lock file")
            self.pidFile.close()
        else:
            print "Force closed!"

        log.critical("Closing!")
        sys.exit()

    def closeEvent(self, event):
        '''When the ui is attempting to exit, run this first.  However, make sure we only do this once'''
        if self.isClosing or self.closeForced:
            self.pidFile.close()

        else:
            #exit = ui.Dialogs.CloseEvent()
            #self.connect(exit, QtCore.SIGNAL("state"), self.closeEventHandler)
            #exit.exec_()
            self.isClosing = True

            if event is 'manual':
                log.debug("Manually closing")
                self.close()

    def findHosts(self):
        '''Get hosts via broadcast packet, add them to self.hosts'''
        self.broadcast = udp.Broadcast.BroadcastSender(self.config)
        self.broadcast.run()

    def addHost(self, hostname, ip):
        '''Add a host to the database and refresh the ui'''
        log.debug("Attempting to add %s (%s) to database" % (hostname, ip))
        if not db.Network.hostExists(SQL, hostname):
            log.info("Added Client: %s" % hostname)
            msg = "Added Host: %s" % hostname
            self.updateConsole("client", msg, color='green')
            db.Network.addHost(SQL, hostname, ip)
            self.refreshHosts()
        else:
            msg = "Host Already In Database: %s" % hostname
            self.updateConsole("client", msg, color='red')
            log.warning(msg)


    def globalPoint(self, widget, point):
        '''Return the global position for a given point on a widget'''
        return widget.mapToGlobal(point)
#################################
### BEGIN Context Menus
#################################
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
#        self.updateConsole('NETWORK', 'Sending frame %s from job %s to %s' % (work[2], work[1], work[0]), 'green')
#
#    def workComplete(self, worker):
#        '''Inform the user of done frames'''
#        ip = worker[0]
#        job = worker[1]
#        frame = worker[2]
#        self.updateConsole('QUEUE', '%s completed frame %s of job %s' % (ip, frame, job), 'brown')
#
#    def killRender(self):
#        '''Kill the current job'''
#        self.ui.cancelRender.setEnabled(False)
#        self.que.emptyQue()
#        # send the kill job to clients !
#        self.updateConsole('QUEUE', '%s frames waiting to render' % self.que.size(), 'brown')
#        self.ui.render.setEnabled(True)
#
#        self.updateConsole('NETWORK', 'Searching for hosts...', 'green')
#        findHosts = udp.Broadcast.BroadcastSender(__UUID__, self)
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
    def updateConsole(self, section, msg, color='black'):
        '''
        Update the ui's status window

        VARS:
            section (string)-- The section to report from (ex. NETWORK)
            msg (string) - The message to post
            color (string) - The color name or hex value to set the section
        '''
        status = '<font color=%s><b>%s</b></font> - %s' % (color, section.upper(), msg)
        self.ui.status.append(status)

#################################
### END General Utilities
#################################

class Testing(QtCore.QObject):
    '''Quick testing code'''
    def __init__(self, parent=None):
        super(Testing, self).__init__(parent)
        log = Logger.Logger("Main.Testing")
        self.config = ReadConfig(CFG_ROOT)
        log.debug("Test code initilized")

    def broadIncriment(self):
        log.netclient("Incrimented")

    def broadDone(self, signal):
        log.netclient("Broadcast complete")

    def sendBroadcast(self):
        '''Send out a broadcast to inform clients of the master node'''
        broadcast = udp.Broadcast.BroadcastSender(self.config, self)
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
    sysinfo = system.Info.SystemInfo(os.path.join(CFG_ROOT, "general.ini"))

    ###############################
    # Command Line Options
    ###############################

    parser = OptionParser(version="PyFarm v%s" % VERSION)
    parser.add_option(
                        "--author", dest="author", action="callback",
                        callback=about.author, help="Return the developer's name"
                     )
    parser.add_option(
                        "--license", dest="license", action="callback",
                        callback=about.license, help="Get the LGPL license header"
                      )
    #parser.add_option("--sysinfo", dest="sysinfo", action="callback",
                        #callback=sysinfo.hardware, help="Get processor, ram, etc. info")
    parser.add_option(
                        "--clean", action="callback",  callback=system.clean,
                        help="remove all byte-compiled Python files"
                     )
    parser.add_option(
                        "--clean-all", action="callback", callback=system.cleanAll,
                        help="In addition to removing all .pyc files also remove \
                        the lock file and database."
                     )
    (options, args) = parser.parse_args()

    ###############################
    # Event Loop Start
    ###############################
    app    = QtGui.QApplication(sys.argv)

    if not UNITTESTS:
        ###############################
        # Unit Tests, for safety!
        ###############################
        testVerbosity = 0

    # prepare test
    from lib.test import ModuleImports
    msg = "Running Unit Test: Version and Module Check"
    #log.info(msg)

    # run test
    #test = unittest.TestLoader().loadTestsFromTestCase(ModuleImports.ModuleTests)
    #unittest.TextTestRunner(verbosity=testVerbosity).run(test)

    # prepare test
    from lib.test import ValidateLogging
    msg = "Running Unit Test: Logging"
    #log.info(msg)

    # run test
    #test = unittest.TestLoader().loadTestsFromTestCase(ValidateLogging.Validate)
    #unittest.TextTestRunner(verbosity=testVerbosity).run(test)

    # prepare test
    from lib.test import ValidateNetConfig
    msg = "Running Unit Test: Network Configuration"
    #log.info(msg)

    # run test
    #test = unittest.TestLoader().loadTestsFromTestCase(ValidateNetConfig.Validate)
    #unittest.TextTestRunner(verbosity=testVerbosity).run(test)

    ###############################
    # Run UI
    ###############################
    main = MainWindow()
    main.show()
    sys.exit(app.exec_())

else:
    log.fatal("This program is not meant to be imported!")