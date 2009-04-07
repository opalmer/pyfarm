#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 12 2009
PURPOSE: Main program to run and manage PyFarm

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
# From Python
from os import getcwd

# From PyQt4
from PyQt4.QtGui import QMessageBox
from PyQt4.QtCore import QThread, QString

# From PyFarm
from lib.Network import AdminClient
from lib.ReadSettings import ParseXmlSettings
from lib.PyFarmExceptions import ErrorProcessingSetup

settings = ParseXmlSettings('%s/settings.xml' % getcwd(), skipSoftware=True)

class ShutdownHostThread(QThread):
    '''
    Threaded instance of shutdown process.
    Created to speed up the shutdown process for multiple
    hosts.

    INPUT:
        host (str) -- ip address of host
    '''
    def __init__(self, host, parent=None):
        super(ShutdownHostThread, self).__init__(parent)
        self.host = host

    def run(self):
        client = AdminClient(self.host, settings.netPort('admin'))
        client.shutdown()


class RestartHostThread(QThread):
    '''
    Threaded instance of restart process.
    Created to speed up the restart process for multiple
    hosts.

    INPUT:
        host (str) -- ip address of host
    '''
    def __init__(self, host, parent=None):
        super(RestartHostThread, self).__init__(parent)
        self.host = host

    def run(self):
        client = AdminClient(self.host, settings.netPort('admin'))
        client.restart()


class CloseEventManager(object):
    '''
    Manager of main gui's processes during
    the close event.  This class is launched when the users
    start the process for exiting PyFarm.

    INPUT:
        data -- instance of data manager of main gui
    '''
    def __init__(self, data, parent=None):
        super(CloseEventManager, self).__init__(parent)
        self.parent = parent
        self.data = data

    def shutdownHosts(self):
        '''Shutdown all remote hosts'''
        for host in self.data.network.hostList():
            client = ShutdownHostThread(host)
            client.run()

    def restartHosts(self):
        '''Restart all remote hosts'''
        for host in self.data.network.hostList():
            client = RestartHostThread(host)
            client.run()

    def exitHelp(self):
        '''
        Inform the user of what/why hosts are restarted or
        shutdown
        '''
        title = QString("Client Action Help")
        help = QString("\
        <p>When closing PyFarm you have two options to deal with\
        remote clients:</p>\
        <p>\
        Select <b><i>yes<i></b> shutdown the remote clients<br>\
        Select <b><i>no<i></b> to restart the remote clients for future use\
        </p>")
        QMessageBox.information(self.parent, title, help)

    def hostExitDialog(self):
        '''Present a host shutdown dialog to the user'''
        return QMessageBox.question(self.parent,
                    QString("Select Client Action"),
                    QString("Would you like to shutdown all remote client nodes?"),
                    QMessageBox.Yes|QMessageBox.No|QMessageBox.Help)
