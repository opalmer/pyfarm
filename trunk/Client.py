#!/usr/bin/env python
#
# INITIAL: May 26 2010
# PURPOSE: To handle and run all client connections on a remote machine
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
import sys

from PyQt4 import QtCore

CWD = os.path.dirname(os.path.abspath(__file__))
PYFARM = CWD
CFG_ROOT = os.path.join(PYFARM, "cfg")
CFG_GEN = os.path.join(CFG_ROOT, "general.ini")

from lib import logger, settings, session, system, net
from lib.net import tcp, udp

logger = logger.Logger()

class Main(QtCore.QObject):
    def __init__(self, parent=None):
        super(Main, self).__init__(parent)
        self.master = net.MasterAddress()
        self.config = settings.ReadConfig.general(CFG_GEN)
        self.pidFile = session.State(context='PyFarm.Client')

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
        portNum = self.config['servers']['broadcast']
        self.broadcast = udp.broadcast.BroadcastReceiever(portNum, parent=self)
        self.connect(
                        self.broadcast,
                        QtCore.SIGNAL("broadcast"),
                        self.setMasterAddress
                    )
        self.broadcast.run()

    def setMasterAddress(self, response):
        '''
        Step 2:
        Set self.master to the incoming ip
        '''
        # master info
        hostname = response[0]
        address = response[1]
        port = int(response[2]['xmlrpc'])

        if self.master.setAddress(hostname, address, port):
            # localhost name, address, and info
            clientName = net.hostname()
            clientAddr = net.address()
            clientSpec = system.hardware.report()

            # send to master
            hosts = tcp.xmlrpc.client(hostname, port, "hosts")
            if hosts.newClient(clientName, clientAddr, clientSpec):
                logger.netclient("Added %s to hosts" % clientName)


if __name__ == '__main__':
    import signal
    from optparse import OptionParser

    # handle ctrl + c signals
    signal.signal(signal.SIGINT, signal.SIG_DFL)

    parser = OptionParser()
    parser.add_option(
                        "--stop", dest="stop",
                        default=False, action="store_true",
                        help="Stop any currently running clients"
                    )

    (options, args) = parser.parse_args()
    app = QtCore.QCoreApplication(sys.argv)
    main = Main(app)

    if options.stop:
        main.stop()

    else:
        main.handlePid()

    sys.exit(app.exec_())
