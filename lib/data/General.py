'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 31, 2009
PURPOSE: Module used to control, manage, and update the general
data dictionary

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

# From PyQt
from PyQt4.QtGui import QMessageBox
from PyQt4.QtCore import QObject, QString, SIGNAL

# From PyFarm
from lib.Info import TypeTest
from lib.ui.main.CloseEvent import CloseEventManager
from lib.ui.main.Status import StatusManager
from lib.ReadSettings import ParseXmlSettings
from lib.ui.main.NetworkTableManager import NetworkTableManager

typeCheck = TypeTest('JobData')
settings = ParseXmlSettings('%s/settings.xml' % getcwd())

class GeneralHostManager(QObject):
    '''
    Contains function for host related information

    COMMON INPUT:
        ip (str) -- ip address of the host we want to examine

    INPUT:
        data (dict) -- dictionary containing the host data
    '''
    def __init__(self, data, uiStatus, parent=None):
        super(GeneralHostManager, self).__init__(parent)
        self.parent = parent
        self.data = data["hosts"]
        self.uiStatus = uiStatus

    def status(self, ip, text=False):
        '''
        Return the status of a host

        INPUT:
            text (True/False) -- if true return the text representation
            of the given value
        '''
        if text:
            return settings.hostStatusKey(self.data[ip]["status"])
        else:
            return self.data[ip]["status"]

    def hostname(self, ip):
        '''Return the hostname of the requested machine'''
        return self.data[ip]["hostname"]

    def os(self, ip):
        '''Return the operating system of the host'''
        return self.data[ip]["os"]

    def architecture(self, ip):
        '''Return the architecture of the host'''
        return self.data[ip]["arch"]

    def rendered(self, ip, string=False):
        '''Return the number of rendered frames'''
        frames = self.data[ip]["rendered"]
        if not string:
            return frames
        else:
            return str(frames)

    def failed(self, ip, string=False):
        '''Return the number of failed frames'''
        frames = self.data[ip]["failed"]
        if not string:
            return frames
        else:
            return str(frames)

    def failureRate(self, ip, string=False):
        '''Return a failure ratio as a decimal'''
        try:
            ratio = float(self.data[ip]["failed"])/float(self.data[ip]["rendered"])
        except ZeroDivisionError:
            ratio = 0.0

        if not string:
            return ratio
        else:
            return str(ratio)

    def softwareDict(self, ip):
        '''Return the entire software dict for the given host'''
        return self.data[ip]["software"]

    def installedVersions(self, ip, software):
        '''Given a generic software return the versions found'''
        for version in self.data[ip]["software"][software]:
            yield version


class GeneralNetworkManager(QObject):
    '''
    Contains functions for network related information

    INPUT:
        data (dict) -- dictionary containing the network data
    '''
    def __init__(self, data, uiStatus, parent=None):
        super(GeneralNetworkManager, self).__init__(parent)
        self.parent = parent
        self.data = data["network"]
        self.uiStatus = uiStatus
        self.host = GeneralHostManager(self.data, self.uiStatus, self)

    def hostList(self):
        '''List all of the current in self.data'''
        return self.data["hosts"].keys()

    def hostCount(self):
        '''Return the current number of hosts'''
        return len(self.data["hosts"].keys())

    def statusHostCount(self, statusValue):
        '''
        Return the current number of hosts matching
        a given status

        INPUT:
            status (int) -- status value to match
        '''
        count = 0
        for ip in self.hostList():
            if self.host.status(ip) == statusValue:
                count += 1
        return count

    def echoNetworkStats(self):
        '''Echo some general network stats'''
        print "Host Stats: \n\tEnabled: %i\n\tActive: %i\n\tDisabled: %i\n\tFailed: %i\n\tTotal Count: %i\n\tHost List:\n\t%s" %\
        (self.enabledHostCount(), self.activeHostCount(), \
        self.disabledHostCount(), self.failedHostCount(), \
        self.hostCount(), self.hostList())

    def enabledHostCount(self):
        '''Return the current number of enabled hosts'''
        return self.statusHostCount(0)

    def activeHostCount(self):
        '''Return the current number of active (rendering) hosts'''
        return self.statusHostCount(1)

    def disabledHostCount(self):
        '''Return the current number of disabled hosts'''
        return self.statusHostCount(2)

    def failedHostCount(self):
        '''Return the current number of disabled hosts'''
        return self.statusHostCount(3)

    def emitSignal(self, sig, data):
        '''Emit a signal from the main class'''
        self.parent.emit(SIGNAL(sig), data)

    def removeHostData(self, ip):
        '''Remove the given host from the dictionary'''
        # remove the entry from the network stats
        #  followed by the dictionary entry
        self.data["stats"][self.host.status(ip)] -= 1
        del self.data["hosts"][ip]

    def removeHost(self, ip):
        '''Remove the given host from the dictionary'''
        status = self.data["hosts"][ip]["status"]
        closeEventManager = CloseEventManager()
        exit_dialog = closeEventManager.singleHostExitDialog(ip)

        if exit_dialog == QMessageBox.Yes:
            print "PyFarm :: Main.removeHost :: Shutting Down %s..." % ip
            closeEventManager.shutdownHost(ip)
            self.removeHostData(ip)

        elif exit_dialog == QMessageBox.No:
            print "PyFarm :: Main.removeHost :: Restarting %s..." % ip
            closeEventManager.restartHost(ip)
            self.removeHostData(ip)

        elif exit_dialog == QMessageBox.Help:
            print "PyFarm :: Main.removeHost :: Presenting host help"
            closeEventManager.singleExitHelp(ip)
            self.removeHost(ip)


class GeneralManager(QObject):
    '''
    General data management, meant for
    program wide information
    '''
    def __init__(self, ui, version, parent=None):
        super(GeneralManager, self).__init__(parent)
        self.ui = ui
        self.data = {"queue" :{
                                "system" : {
                                    "status" : 0,
                                    "failureRate" : 0
                                    },
                                "jobs" : {
                                     "waiting" : 0,
                                     "running" : 0,
                                     "failed" : 0,
                                     "complete" : 0},
                                "frames":{
                                    "minTime" : 0,
                                    "maxTime" : 0,
                                    "avgTime" : 0,
                                    "frameCount" : 0,
                                    "waiting": 0,
                                    "rendering" : 0,
                                    "failed": 0,
                                    "paused" : 0},
                                },
                            "network" : {
                                "stats" :{
                                    0: 0,
                                    1: 0,
                                    2: 0,
                                    3: 0
                                    },
                                "hosts" : {
                                                }
                                }
                    }
        self.uiStatus = StatusManager(self, ui, version)
        self.network = GeneralNetworkManager(self.data, self.uiStatus, self)
        self.netTable = NetworkTableManager(self, self.ui.networkTable)

    def addHost(self, ip, hostname, os='', arch='', software={}, basic=False):
        '''
        Add a host to the system information dictionary

        ip (str) -- ip address of host
        hostname (str) -- hostname
        os (str) -- operating system type of host
        arch (str) -- architecture of system
        software (dict) -- dictionary of installed software
        basic (True/False) -- if true ,only add the hostname,ip address, and status
        '''
        if basic:
            self.data["network"]["hosts"][ip] ={"hostname" : hostname,
                                                                        "status": 0,
                                                                        "rendered" : 0,
                                                                        "failed" : 0
                                                                        }
        else:
            if typeCheck.isDict(software):
                self.data["network"]["hosts"][ip] ={"hostname" : hostname,
                                                                            "status": 0,
                                                                            "os" : os,
                                                                            "arch" : arch,
                                                                            "software" : software,
                                                                            "rendered" : 0,
                                                                            "failed" : 0
                                                                            }
        self.data["network"]["stats"][0] += 1
        self.netTable.addHostToTable(ip)
        self.uiStatus.network.refreshConnected()


    def removeHost(self):
        '''
        Remove the selected hosts from the table and
        data dictionary
        '''
        # get the ip and row
        row = self.ui.networkTable.currentRow()
        ip = str(self.ui.networkTable.item(row, 0).text())

        # ask the user what to do, and from there remove the data if requested
        # finally, remove the row
        self.network.removeHost(ip)
        self.ui.networkTable.removeRow(row)
        self.uiStatus.network.refreshConnected()

    def emitSignal(self, sig, data=None):
        '''Emit a signal from the main class'''
        if data:
            self.emit(SIGNAL(sig), data)
        else:
            self.emit(SIGNAL(sig))

    def dataGeneral(self):
        '''Return the general data dictionary for use outside of the class'''
        return self.data
