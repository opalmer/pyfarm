'''
AUTHOR: Oliver Palmer
HOMEPAGE: www.pyfarm.net
INITIAL: March 31, 2009
PURPOSE: Module used to control, manage, and update the job dictionary.

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
import time
from sys import exit
from os import getcwd

# From PyQt
from PyQt4.QtGui import QMessageBox
from PyQt4.QtCore import QObject, QString, SIGNAL

# From PyFarm
from ReadSettings import ParseXmlSettings
from Info import Statistics, TypeTest
from PyFarmExceptions import ErrorProcessingSetup
from lib.ui.main.CloseEvent import CloseEventManager

settings = ParseXmlSettings('%s/settings.xml' % getcwd(), skipSoftware=True)
statistics = Statistics()
typeCheck = TypeTest('JobData')
error = ErrorProcessingSetup('JobData')

class JobStatus(QObject):
    '''
    Return status information about a job

    INPUT:
        job (dict) -- job dictionary to work with
        name (str) -- job name
        generalData (mapped class) -- instance of general class that contains
        program wide information
    '''
    def __init__(self, job, name, generalData, parent=None):
        super(JobStatus, self).__init__(parent)
        self.job = job
        self.name = name
        self.general = generalData
        self.parent = parent
        self.modName = 'JobStatus'

    def overall(self):
        '''
        Return the overall job status

        INPUT:
            id (str) -- subjob id
        '''
        subjobs = []
        for subjob in self.job.keys():
            subjobs.append(subjob)
        return statistics.mode(subjob)

    def subjob(self, id, emitSignal=False):
        '''Return the status of a subjob'''
        statusList = []
        for frame in self.job[id]["frames"].keys():
            statusList.append(self.frame(id, frame))

        # get mode, set subjob status
        status = statistics.get(statusList, 'mode')
        self.setSubjob(id, status)
        return status

    def setSubjob(self, id, status):
        '''
        Set the status of the given subjob

        INPUT:
            id (str)  -- subjob id
            status (int) [0-4] --status index of subjob
        '''
        typeCheck.isString(id)
        typeCheck.isInt(status)
        if status >= 0 and status <= 4:
            self.job[id]["status"] = status
            self.parent.emitSignal("subjobStatusChanged", [self.name, id, status])
        else:
            raise error.valueError(0, 4, status)

    def frame(self, id, frame):
        '''Return the status of a frame'''
        return self.job[id]["frames"][frame]["status"]

    def setFrame(self, id, frame):
        '''
        Set the status of the given frame

        INPUT:
            id (str) -- subjob id
            frame (int) -- frame number to set
        '''
        pass

    def startFrame(self, id, frame):
        '''
        Set the frame start time and set
        status to rendering.

        INPUT:
            id (str) -- subjob id
            frame (int) -- frame to change
        '''
        pass

    def listSubjobs(self):
        '''List the subjobs contained in self.job'''
        return self.job.keys()

    def listFrames(self, id=False):
        '''
        If id is given, list the frames of the id.  If
        id is not given, list ALL frames.

        INPUT:
            id (str) --  subjob id (optional, if not set ALL frames will be returned)
        '''
        if id:
            for frame in self.job[id]["frames"].keys():
                yield frame
        else:
            for subjob in self.listSubjobs():
                for frame in self.job[subjob]["frames"].keys():
                    yield frame

    def listWaitingFrames(self, id=False):
        '''
        For each subjob, return a list of currently waiting
        frames (unless we specify an id)

        INPUT:
            id (str) -- subjob id (optional, if not given all waiting frames will be returned)
        '''
        if id:
            pass
        else:
            for subjob in self.listSubjobs():
                yield

    def frameCount(self, id=False):
        '''
        Return a frame count for the job/subjob

        INPUT:
            id (str) -- subjob id (optional, if not set return TOTAL frame count)
        '''
        if id:
            return len(self.job[id]["frames"].keys())
        else:
            frameTotal = 0
            for subjob in self.listSubjobs():
                frameTotal += len(self.job[subjob]["frames"].keys())

            return frameTotal

    def subjobCount(self):
        '''Return a subjob count'''
        return len(self.job.keys())


class JobData(QObject):
    '''
    Add or modify a job

    INPUT:
        job (mapped class) -- job dictionary to work with
        name (str) - job name of data
        generalData (mapped class) -- instance of general class that contains
        program wide information
    '''
    def __init__(self, job, name, generalData, parent=None):
        super(JobData, self).__init__(parent)
        self.job = job
        self.name = name
        self.general = generalData
        self.parent = parent
        self.modname = 'JobData'

    def createSubjob(self, id, priority=5):
        '''
        Create a sub job

        INPUT:
            id (str) -- subjob id to create
            priority (int) -- priority to give job
        '''
        # first check the types of the input vars
        typeCheck.isString(id)
        typeCheck.isInt(priority)

        if priority >= 1 and priority <= 10:
            self.job[id] = {"priority" : priority, "status" : 0,
                                    "statistics": {
                                        "frames" : {
                                            "minTime" : 0,
                                            "maxTime" : 0,
                                            "avgTime" : 0,
                                            "frameCount" : 0,
                                            "waiting": 0,
                                            "rendering" : 0,
                                            "failed": 0,
                                            "paused" : 0
                                            }},
                                    "frames" : {}
                                    }
        else:
            exit('PyFarm :: %s.createSubjob :: ERROR :: Priority must be <= 10 and >= 1') % self.modName

    def addFrame(self, id, frame, software, command):
        '''
        add a frame to the job

        INPUT:
            subid (str) --  subjob to add the frame to
            frame (int) --  frame number
            software (str) -- software package to render with
            command (str) -- command to render with
        '''
        # first check the types of the input vars
        #  not all items are check because they been checked
        #  already.
        typeCheck.isInt(frame)
        typeCheck.isString(software)
        typeCheck.isString(command)

        entry = {"status" : 0, "host" : None,
                        "pid" : None, "software" : software,
                        "start" : None, "end" : None, "elapsed" : None,
                        "command" : command
                        }

        # add the frame to the frames dictionary
        self.job[id]["frames"].update({frame : entry})

        # inform the gui of the new frame
        # self.emit(SIGNAL("newFrame"), [id, frame, entry])
        self.parent.emitSignal("newFrame", [self.name, id, frame, entry])

        # update the subjob statistics
        self.job[id]["statistics"]["frames"]["frameCount"] += 1
        self.job[id]["statistics"]["frames"]["waiting"] += 1


class JobManager(QObject):
    '''
    General job manager used to manage and
    setup a job
    '''
    def __init__(self, name, generalData, parent=None):
        super(JobManager, self).__init__(parent)
        self.name = name
        self.general = generalData
        self.job = {}
        self.data = JobData(self.job, self.name, self.general, self)
        self.status = JobStatus(self.job, self.name, self.general, self)

    def emitSignal(self, sig, emission):
        '''
        Pass a signal emission from a child function to the upperjob1 has 2 subjobs and 10 frames

        level

        INPUT:
            sig (str) -- name of signal to emit
            emission (str, list, dict) -- information to emit
        '''
        self.emit(SIGNAL(sig), emission)

    def jobData(self):
        '''Return the dictionary, for previewing'''
        return self.job


class GeneralHostManager(QObject):
    '''
    Contains function for host related information

    COMMON INPUT:
        ip (str) -- ip address of the host we want to examine

    INPUT:
        data (dict) -- dictionary containing the host data
    '''
    def __init__(self, data, parent=None):
        super(GeneralHostManager, self).__init__(parent)
        self.parent = parent
        self.data = data["hosts"]

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
    def __init__(self, data, parent=None):
        super(GeneralNetworkManager, self).__init__(parent)
        self.parent = parent
        self.data = data["network"]
        self.host = GeneralHostManager(self.data, self)

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
        self.echoNetworkStats()

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
    def __init__(self, parent=None):
        super(GeneralManager, self).__init__(parent)
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
        self.network = GeneralNetworkManager(self.data, self)

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
        self.emitSignal('status_update')

    def emitSignal(self, sig, data=None):
        '''Emit a signal from the main class'''
        if data:
            self.emit(SIGNAL(sig), data)
        else:
            self.emit(SIGNAL(sig))

    def dataGeneral(self):
        '''Return the general data dictionary for use outside of the class'''
        return self.data
