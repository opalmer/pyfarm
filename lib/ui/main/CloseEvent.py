'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 6 2009
PURPOSE: Close event classes, run when PyFarm is being closed by the user

    This file is part of PyFarm.
    Copyright (C) 2008-2009 Oliver Palmer

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
from PyQt4.QtCore import QThread, QString, QObject, SIGNAL

# From PyFarm
from lib.network.Admin import AdminClient
from lib.ReadSettings import ParseXmlSettings
from lib.PyFarmExceptions import ErrorProcessingSetup

settings = ParseXmlSettings('%s/settings.xml' % getcwd(), skipSoftware=True)

class CloseEventManager(QObject):
    '''
    Manager of main gui's processes during
    the close event.  This class is launched when the users
    start the process for exiting PyFarm.

    Module also used for individual host management.

    INPUT:
        data -- instance of data manager of main gui
    '''
    def __init__(self, data=None, parent=None):
        super(CloseEventManager, self).__init__(parent)
        self.parent = parent
        self.data = data

    def shutdownHosts(self):
        '''Shutdown all remote hosts'''
        for host in self.data.network.hostList():
            client = AdminClient(host, settings.netPort('admin'))
            client.shutdown()

    def restartHosts(self):
        '''Restart all remote hosts'''
        for host in self.data.network.hostList():
            client = AdminClient(host, settings.netPort('admin'))
            client.restart()

    def shutdownHost(self, ip):
        '''Shutdown a single host'''
        client = AdminClient(ip, settings.netPort('admin'))
        client.shutdown()

    def restartHost(self, ip):
        '''Restart a single host'''
        client = AdminClient(ip, settings.netPort('admin'))
        client.restart()

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

    def singleHostExitDialog(self, ip):
        '''Present a host shutdown dialog to the user'''
        return QMessageBox.question(self.parent,
                    QString("Select Client Action"),
                    QString("Would you like to shutdown %s?" % ip),
                    QMessageBox.Yes|QMessageBox.No|QMessageBox.Help)

    def singleExitHelp(self, ip):
        '''
        Inform the user of what/why hosts are restarted or
        shutdown (for a single machine)
        '''
        title = QString("Client Action Help")
        help = QString("\
        <p>When removing a %s you have two options</p>\
        <p>Select <b><i>yes<i></b> shutdown %s<br>\
        Select <b><i>no<i></b> to restart %s for future use</p>" % (ip, ip, ip))
        QMessageBox.information(self.parent, title, help)
