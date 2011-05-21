# No shebang line, this module is meant to be imported
#
# INITIAL: July 17 2010
# PURPOSE: Slot library for quick access to small actions
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
import time

from PyQt4 import QtGui, QtCore

import ui
import logger
from net import udp

logger = logger.Logger()

class Help(object):
    '''Slot functions from the help menu'''
    def __init__(self,parent, config):
        self.parent = parent
        self.ui = parent.ui
        self.config = config


class Host(QtCore.QObject):
    '''Host related slot functions'''
    def __init__(self, parent, config, services):
        super(Host, self).__init__(parent)
        self.parent = parent
        self.ui = parent.ui
        self.config = config
        self.services = services

        # establish broadcast related variables
        self.lastBroadcast = 0
        self.nextBroadcast = 0
        self.broadcastDelay = self.config['broadcast']['delay']

    def find(self):
        '''Search for hosts running the client program'''
        if time.time() >= self.nextBroadcast:
            self.lastBroadcast = time.time()
            self.nextBroadcast = self.lastBroadcast + self.broadcastDelay
            broadcast = udp.broadcast.BroadcastSender(self.config, self.services)
            progress = ui.dialogs.BroadcastProgress(broadcast, self.ui)
            self.connect(progress, QtCore.SIGNAL("canceled()"), broadcast.quit)
            progress.show()
            broadcast.start()

        elif not time.time() >= self.nextBroadcast:
            delay = self.broadcastDelay
            warn = "Please wait at least %i seconds " % delay
            warn += "between broadcasts before attempting another another."
            QtGui.QMessageBox.warning(
                                        self.ui, "Broadcast Delayed",
                                        warn, "Ok", "Cancel"
                                     )

    def remove(self):
        '''Remove the selected host from the sql and ui'''
        indexes = self.ui.networkTable.selectedIndexes()
        if len(indexes):
            data = {}
            for index in indexes:
                # create the initial host dictionary
                if not data.has_key(index.row()):
                    data[index.row()] = {}

                # get host information by column
                host = data[index.row()]
                if index.column() == 0:
                    host['hostname'] = index.data().toString()

                elif index.column() == 1:
                    host['ip'] = index.data().toString()

            # itereate over each host and remove it
            for key in data.keys():
                host = data[key]
                logger.info("Removing Host: %s (%s)" % (host['hostname'], host['ip']))
                if sql.Network.removeHost(sql, host['hostname']):
                    self.ui.refreshHosts()
        else:
            logger.error("Not enough items selected")

class Slots(object):
    '''Main slots object, all other slots are referenced from here'''
    def __init__(self, parent, config, services):
        self.help = Help(parent, config)
        self.host = Host(parent, config, services)
