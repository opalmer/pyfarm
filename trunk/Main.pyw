#!/usr/bin/env python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 12 2009
PURPOSE: Main program to run and manage PyFarm

    This file is part of PyFarm.
    Copyright (C) 2008-2011 Oliver Palmer

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
UI_FILE   = os.path.join(PYFARM, "lib", "ui", "MainWindow.ui")
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

# setup logging
log = Logger.Logger(MODULE, LOGLEVEL)

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
        self.ui = uic.loadUi(UI_FILE, baseinstance=self)

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

        # setup database
        netTable       = self.ui.networkTable
        jobTable       = self.ui.currentJobs
        SqlTable       = ui.SqlTables
        netColumns     = ("hostname", "ip", "status")
        netSort        = "hostname"
        self.hostTable = SqlTable.Manager(SQL, netTable, "hosts", netColumns, sort=netSort)

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
        self.connect(
                        self.ui.refreshUi,
                        QtCore.SIGNAL("pressed()"),
                        self.manualRefresh
                    )

        # general setup and variables
        self.isClosing = False
        self.config    = Settings.ReadConfig.general(CFG_GEN)
        self.slots     = Slots.Slots(self, self.config, SQL)
        self.runServers()

    def refreshHosts(self):
        '''Refresh the hosts table by refreshing the database model'''
        self.hostTable.refresh()

    def manualRefresh(self):
        '''Force a refresh'''
        self.refreshHosts()

    def timerRefresh(self):
        '''
        After the global timer expires, run this function to update the
        interface, tables, etc.

        NOTE: This function can be intensive, try to keep refresh events
        to a minimum.
        '''
        log.notimplemented("This function has not been implemented yet,")
        log.notimplemented("...pending evalulation of updating individual")
        log.nogimplemented("...table fields")

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
        listenAddress    = QtNetwork.QHostAddress(QtNetwork.QHostAddress.Any)
        self.queueServer = tcp.Queue.QueueServer(main=self)
        self.adminServer = tcp.Admin.AdminServer(main=self)
        queueServerPort  = self.config['servers']['queue']
        adminServerPort  = self.config['servers']['admin']

        try:

            if not self.queueServer.listen(listenAddress, queueServerPort):
                errStr = self.queueServer.errorString()
                error  = "Could not start the queue server: %s" % errStr
                log.fatal(error)

                if not DEBUG:
                    raise lib.net.ServerFault(error)
                else:
                    log.warning("Bypassing exception!!!")

        except TypeError:
            log.critical("Invalid type passed to queueServer.listen")
            self.updateConsole(
                                "server.error", "Failed to start Queue Server",
                                color="red"
                              )

        try:
            if not self.adminServer.listen(listenAddress, adminServerPort):
                errStr = self.adminServer.errorString()
                error  = "Could not start the admin server: %s" % errStr
                log.fatal(error)

                if not DEBUG:
                    raise lib.net.ServerFault(error)
                else:
                    log.warning("Bypassing exception!!!")

        except TypeError:
            log.critical("Invalid type passed to adminServer.listen")
            self.updateConsole(
                                "server.error", "Failed to start Admin Server",
                                color="red"
                              )

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
    def hostRemovePressed(self): self.slots.host.remove()
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
            try:
                for key, value in exitAnswers.items():
                    log.ui("Exit State Choice: %s -> %s" % (key, str(value)))
            except:
                log.error("Cannot show exit answers, dialog box is disabled")

            log.info("Removing lock file")
            self.pidFile.close()

        else:
            print "Force closed!"

        log.critical("Closing!")
        sys.exit()

    def closeEvent(self, event):
        '''When the ui is attempting to exit, run this first.  However, make sure we only do this once'''
        self.pidFile.close()
        #exit = ui.Dialogs.CloseEvent()
        #self.connect(exit, QtCore.SIGNAL("state"), self.closeEventHandler)
        #exit.exec_()

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
    import lib.InputFlags as flags
    from optparse import OptionParser
    about   = flags.About(DEVELOPER, 'GNU-LGPL_Header.txt')
    sysinfo = system.Info.SystemInfo(os.path.join(CFG_ROOT, "general.ini"))

    # Command Line Options
    parser = OptionParser(version="PyFarm v%s" % VERSION)
    parser.add_option(
                        "--author", dest="author", action="callback",
                        callback=about.author, help="Return the developer's name"
                     )
    parser.add_option(
                        "--license", dest="license", action="callback",
                        callback=about.license, help="Get the LGPL license header"
                      )
    parser.add_option(
                        "--db", dest="db", default=db.DB_SQL,
                        help="Change default database location"
                     )
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

    # Begin event loop
    app = QtGui.QApplication(sys.argv)
    SQL = db.connect(options.db)

    # lower verbosity
    if not UNITTESTS:
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