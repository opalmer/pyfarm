'''
HOMEPAGE: www.pyfarm.net
INITIAL: July 17 2010
PURPOSE: Slot library for quick access to small actions

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

CWD      = os.path.dirname(os.path.abspath(__file__))
PYFARM   = os.path.abspath(os.path.join(CWD, ".."))
MODULE   = os.path.basename(__file__)
LOGLEVEL = 2
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import logger, db
from lib.net.udp.broadcast import BroadcastSender

log = logger.Logger(MODULE, LOGLEVEL)

class Help(object):
    '''Slot functions from the help menu'''
    def __init__(self,parent, config):
        self.parent = parent
        self.ui     = parent.ui
        self.config = config


class Host(object):
    '''Host related slot functions'''
    def __init__(self, parent, config, sql):
        self.log    = logger.Logger("slots.Host", LOGLEVEL)
        self.sql    = sql
        self.parent = parent
        self.ui     = parent.ui
        self.config = config

    def find(self):
        '''Search for hosts running the client program'''
        self.broadcast = BroadcastSender(self.config)
        self.broadcast.start()

    def remove(self):
        '''Remove the selected host from the db and ui'''
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
                self.log.info("Removing Host: %s (%s)" % (host['hostname'], host['ip']))
                if db.Network.removeHost(self.sql, host['hostname']):
                    self.ui.refreshHosts()
        else:
            self.log.error("Not enough items selected")

class Slots(object):
    '''Main slots object, all other slots are referenced from here'''
    def __init__(self, parent, config, sql):
        self.sql  = sql
        self.help = Help(parent, config)
        self.host = Host(parent, config, sql)
