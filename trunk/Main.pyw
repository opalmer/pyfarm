#!/usr/bin/env python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 12 2009
PURPOSE: Main program to run and manage PyFarm

    This file is part of PyFarm.
    Copyright (C) 2008-2011 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either __version__ 3 of the License, or
    (at your option) any later __version__.

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
CFG_ROOT  = os.path.join(PYFARM, "cfg")
ICN_ROOT  = os.path.join(PYFARM, "icons")
QUE_ICN   = os.path.join(ICN_ROOT, "queue")
CFG_GEN   = os.path.join(CFG_ROOT, "general.ini")
UI_FILE   = os.path.join(PYFARM, "lib", "ui", "mainWindow.ui")
PIDFILE   = None
DEBUG     = False
UNITTESTS = False

__author__  = "Oliver Palmer"
__version__ = "0.5.0"

import cfg.resources_rc
import lib.net, lib.net.errors
from lib.net import tcp
from lib import db, logger, ui, slots, settings, session, system

# setup logging
logger = logger.Logger()

class MainWindow(QtGui.QMainWindow):
    '''This is the controlling class for the main gui'''
    def __init__(self):
        super(MainWindow, self).__init__()

        self.pidFile     = session.State(context='PyFarm.Main')
        self.closeForced = False

        if self.pidFile.running():
            self.handlePid()

        else:
            self.pidFile.write()

        PIDFILE = self.pidFile.pidFile

        # load the ui file
        self.ui = uic.loadUi(UI_FILE, baseinstance=self)

        # setup layouts
        self.setWindowTitle("PyFarm -- __version__ %s" % __version__)
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
        SqlTable       = ui.sqlTables
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
        self.config    = settings.ReadConfig.general(CFG_GEN)
        self.services  = lib.net.Services()
        self.slots     = slots.Slots(self, self.config, self.services, SQL)
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
        logger.notimplemented("This function has not been implemented yet,")
        logger.notimplemented("...pending evalulation of updating individual")
        logger.nogimplemented("...table fields")

    def handlePid(self):
        '''Handle actions relating to the process id file'''
        title  = "Main.pyw Is Already Running"
        msg    = "PyFarm already seems to be open, terminate the running"
        msg   += " process if needed and continue?"
        logger.warning("%s: User input required" % title)
        yes    = QtGui.QMessageBox.Yes
        no     = QtGui.QMessageBox.No
        msgBox = QtGui.QMessageBox.warning(
                                            self, title, msg,
                                            yes|no
                                          )

        if msgBox == yes:
            self.pidFile.write(force=True)

        else:
            logger.warning("Not overwriting PID file")

    def _serverError(self, server, serverName):
        '''Format, display, and present a server error to the ui and console'''
        msg   = (serverName, str(server.errorString()))
        error = "Could not start the %s server: %s" % msg
        self.updateConsole("server", error, color="darkblue")
        logger.fatal(error)

    def _serverRunning(self, server, serverName):
        '''
        Format, display, and present information about a running server to the
        interface and console
        '''
        msg  = (serverName, server.serverPort())
        info = "%s running on port %i" % msg
        self.updateConsole("server", info, color="green")
        logger.netserver(info)

    def runServers(self):
        '''Run the background servers required to operate PyFarm'''
        listenAddress  = lib.net.address(convert=False)

        # create resources for xmlrpc server
        queue = tcp.queue.Resource()
        hosts = tcp.hosts.Resource()

        self.rpcServer = tcp.xmlrpc.BaseServer()
        self.rpcServer.addResource("queue", queue)
        self.rpcServer.addResource("hosts", hosts)

        if not self.rpcServer.listen():
            self._serverError(self.rpcServer, "RPCServer")

        else:
            self._serverRunning(self.rpcServer, "RPCServer")

        netinfo = "<b>Hostname:</b> %s, <b>IP:</b> %s, <b>Physical Address:</b> %s" % (
                        lib.net.hostname(),
                        lib.net.address(convert=True),
                        lib.net.hardwareAddress()
                    )
        self.updateConsole("network.setup", netinfo, color="green")

        # pass hostname, address, and service information to the
        # services dictionary
        self.services.setHostname(lib.net.hostname())
        self.services.setAddress(lib.net.address(convert=True))
        self.services.addService("xmlrpc", self.rpcServer.serverPort())

    # MainWindow slots, actions, and processes
    # Other actions could include:
    ## slots.stats
    ## slots.state (current software, job, crons, etc.)
    def submitAction(self, action):
        '''Return a QIcon object with icon preloaded'''
        logger.ui("Submitted With: %s" % action.text())

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
                    logger.ui("Exit State Choice: %s -> %s" % (key, str(value)))
            except:
                logger.error("Cannot show exit answers, dialog box is disabled")

            logger.info("Removing lock file")
            self.pidFile.close()

        else:
            logger.warning("Force closed!")

        logger.critical("Closing!")
        sys.exit()

    def closeEvent(self, event):
        '''When the ui is attempting to exit, run this first.  However, make sure we only do this once'''
        self.pidFile.close()

    def addHost(self, hostname, ip, mode="new"):
        '''Add a host to the database and refresh the ui'''
        logger.debug("Attempting to add %s (%s) to database" % (hostname, ip))
        if mode == "new":
            msg = "Adding Host: %s [%s]" % (hostname, ip)
            self.updateConsole("client", msg, color='green')

        elif mode == "refresh":
            msg = "Refreshing Host: %s [%s]" % (hostname, ip)
            logger.debug(msg)
#        if not db.Network.hostExists(SQL, hostname):
#            logger.info("Added Client: %s" % hostname)
#            msg = "Added Host: %s" % hostname
#            self.updateConsole("client", msg, color='green')
#            db.Network.addHost(SQL, hostname, ip)
#            self.refreshHosts()
#        else:
#            msg = "Host Already In Database: %s" % hostname
#            self.updateConsole("client", msg, color='red')
#            logger.warning(msg)

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
        logger = Logger.Logger("Main.Testing")
        self.config = ReadConfig(CFG_ROOT)
        logger.debug("Test code initilized")

    def broadIncriment(self):
        logger.netclient("Incrimented")

    def broadDone(self, signal):
        logger.netclient("Broadcast complete")

    def runStatusServer(self):
        '''Run the status server and listen for connections'''
        logger.netserver("Running status server")

    def run(self, option=None, opt=None, value=None, parser=None):
        logger.debug("Running test code")

        self.runStatusServer()
        self.sendBroadcast()

        logger.debug("Test run complete")
        logger.terminate("Testing Terminated")

if __name__ != '__MAIN__':
    import signal
    import lib.inputFlags as flags
    from optparse import OptionParser
    about   = flags.About(__author__, 'GNU-LGPL_Header.txt')
    sysinfo = system.info.SystemInfo(os.path.join(CFG_ROOT, "general.ini"))

    # handle ctrl + c signals
    signal.signal(signal.SIGINT, signal.SIG_DFL)

    # Command Line Options
    parser = OptionParser(version="PyFarm v%s" % __version__)
    parser.add_option(
                        "--author", dest="author", action="callback",
                        callback=about.author, help="Return the __author__'s name"
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
    from lib.test import testImports
    msg = "Running Unit Test: __version__ and Module Check"
    #logger.info(msg)

    # run test
    #test = unittest.TestLoader().loadTestsFromTestCase(ModuleImports.ModuleTests)
    #unittest.TextTestRunner(verbosity=testVerbosity).run(test)

    # prepare test
    from lib.test import testLogging
    msg = "Running Unit Test: Logging"
    #logger.info(msg)

    # run test
    #test = unittest.TestLoader().loadTestsFromTestCase(ValidateLogging.Validate)
    #unittest.TextTestRunner(verbosity=testVerbosity).run(test)

    # prepare test
    from lib.test import testNetConfig
    msg = "Running Unit Test: Network Configuration"
    #logger.info(msg)

    # run test
    #test = unittest.TestLoader().loadTestsFromTestCase(ValidateNetConfig.Validate)
    #unittest.TextTestRunner(verbosity=testVerbosity).run(test)

    ###############################
    # Run UI
    ###############################
    main = MainWindow()
    main.show()
    app.exec_()

    # be sure we cleanup the pid file
    try:
        os.remove(PIDFILE)
    except: pass
    finally:
        sys.exit(0)
else:
    logger.fatal("This program is not meant to be imported!")
