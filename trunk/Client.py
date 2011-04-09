#!/usr/bin/env python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 26 2010
PURPOSE: To handle and run all client connections on a remote machine

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

from PyQt4 import QtCore

CWD       = os.path.dirname(os.path.abspath(__file__))
PYFARM    = CWD
MODULE    = os.path.basename(__file__)
CFG_ROOT  = os.path.join(PYFARM, "cfg")
CFG_GEN   = os.path.join(CFG_ROOT, "general.ini")
CONTEXT   = MODULE.split(".")[0]

from lib.net import tcp, udp
from lib import logger, settings, session

logger = logger.Logger(MODULE)

class Main(QtCore.QObject):
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        self.config         = settings.ReadConfig.general(CFG_GEN)
        self.pidFile        = session.State(context=CONTEXT)

        # network pre-setup
        self.queueServer    = None
        self.adminServer    = None
        self.masterHostname = ''
        self.masterAddress  = ''
        self.newMaster      = False

    def handlePid(self):
        '''Handle actions relating to the process id file'''
        if self.pidFile.running() or self.pidFile.exists():
            msg = "%s You cannot run more than one client at once.%s" % (os.linesep, os.linesep)
            msg += "Would you like to shutdown the other client process? ([y]/n) "
            #response = raw_input(msg)
            response = "y"
            logger.warning("Forced overwrite")

            if response.strip().lower() == "y":
                self.pidFile.write(force=True)
                self.listenForBroadcast()

            else:
                logger.critical("You can only run one client at a time")
                sys.exit(1)

        else:
            self.pidFile.write()
            self.listenForBroadcast()

    def stop(self):
        '''Stop any currently running clients'''
        logger.info("Attempting to stop client process")
        if self.pidFile.exists():
            self.pidFile.kill()
            self.pidFile.remove()

        logger.info("Exiting")
        sys.exit(0)

    def listenForBroadcast(self):
        '''
        Step 1:
        Listen for an incoming broadcast from the master
        '''
        logger.deprecated("Broadcast port allocation is now handled by lib.net")
        self.broadcast = udp.broadcast.BroadcastReceiever(self.config, parent=self)
        self.connect(
                        self.broadcast,
                        QtCore.SIGNAL("masterFound"),
                        self.setMasterAddress
                    )
        self.connect(
                        self.broadcast,
                        QtCore.SIGNAL("services"),
                        self.startServers
                    )
        self.broadcast.run()

    def setMasterAddress(self, response):
        '''
        Step 2:
        Set self.master to the incoming ip
        '''
        newName = False
        newAddr = False

        if response[0] != self.masterHostname or response[0] != self.masterAddress:
            self.newMaster      = True
            self.masterHostname = response[0]
            self.masterAddress  = response[1]

        # check for new hostname
        if response[0] != self.masterHostname:
            self.masterHostname  = response[0]
            newName              = True

        # check for new address
        if response[1] != self.masterAddress:
            self.masterAddress = response[1]
            newName            = True

        queue = tcp.queue.QueueClient(self.config, self.masterAddress)
        if newName or newAddr:
            logger.info("Received master address")
            queue.addClient()

        else:
            logger.info("Already connected to master: %s" % response[0])
            queue.addClient(new=False)

    def startServers(self, services):
        '''
        Step 3:
        Startup the servers on the proper ports and send client info to the
        master host.
        '''
        if self.masterAddress and self.newMaster:
            logger.netclient("Sending client info to %s" % self.masterAddress)
            queueClient    = tcp.queue.QueueClient(
                                                    self.masterAddress,
                                                    services['queue']
                                                  )
            self.newMaster = False

            # be sure we shutdown any running servers first
            if self.admin: self.admin.close()
            if self.queue: self.queue.close()

            logger.netserver("Starting admin server")
            self.admin = tcp.admin.AdminServer(self)
            if not self.admin.listen(services['admin']):
                errStr = self.admin.errorString()
                error  = "Could not start the admin server: %s" % errStr
                logger.fatal(error)

            else:
                logger.netserver("...admin server running")

            logger.netserver("Starting queue server")
            self.queue = tcp.queue.QueueServer(self)
            if not self.queue.listen(services['queue']):
                errStr = self.queue.errorString()
                error  = "Could not start the queue server: %s" % errStr
                logger.fatal(error)

            else:
                logger.netserver("...queue server running")


if __name__ == '__main__':
    from optparse import OptionParser

    parser = OptionParser()
    parser.add_option(
                        "--stop", dest="stop",
                        default=False, action="store_true",
                        help="Stop any currently running clients"
                    )

    (options, args) = parser.parse_args()
    app  = QtCore.QCoreApplication(sys.argv)
    main = Main(app)

    if options.stop:
        main.stop()

    else:
        main.handlePid()

    sys.exit(app.exec_())
