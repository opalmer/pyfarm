'''
AUTHOR: Oliver Palmer
HOMEPAGE: www.pyfarm.net
INITIAL: April 6 2009
PURPOSE: To manage the network table and display relevant data

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
from PyQt4.QtGui import *
from lib.Network import ResolveHost
from lib.ui.main.CloseEvent import CloseEventManager

class NetworkTableManager(object):
    '''
    Class meant to handly PyFarm's network table

    INPUT:
        table -- instanced table to operate on
    '''
    def __init__(self, data, ui, parent=None):
        super(NetworkTableManager, self).__init__(parent)
        self.data = data # == self.dataGeneral
        self.ui = ui
        self.parent = parent

        # initial setup
        self.ui.horizontalHeader().setStretchLastSection(True)
        self.ui.setAlternatingRowColors(True)

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
            hostname = ResolveHost(host)[0]
            ip = ResolveHost(host)[1]

            # if the current host has not been added
            if ip not in self.data.hostList():
                self.getSystemInfo(ip)
                print "now here"

        self.data.network.echoNetworkStats()

    def addHostToTable(self, ip):
        '''
        Look info the data dictionary and update the host list

        INPUT:
            ip (str) -- ip of host to add
        '''
        # gather host information, create widget items for each
        ipwidget = QTableWidgetItem(ip)
        hostname = QTableWidgetItem(self.data.network.host.hostname(ip))
        status = QTableWidgetItem(self.data.network.host.status(ip, text=True))

        # insert a row
        rowcount = self.ui.rowCount()
        self.ui.insertRow(rowcount)

        # add thems to the table
        self.ui.setItem(rowcount, 0, ipwidget)
        self.ui.setItem(rowcount, 1, hostname)
        self.ui.setItem(rowcount, 2, status)

    def removeSelectedHost(self):
        '''
        Remove the selected hosts from the table and
        data dictionary
        '''
        row = self.ui.currentRow()
        ip = self.ui.item(row, 0).text()
        self.data.network.removeHost(ip)
        self.ui.removeRow(row)
        client = CloseEventManager
        client.shutdownHost(str(ip))
