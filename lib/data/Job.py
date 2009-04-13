'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 31, 2009
PURPOSE: Module used to control, manage, and update the job dictionary.

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
from sys import exit
from os import getcwd

# From PyQt
from PyQt4.QtCore import QObject, SIGNAL

# From PyFarm
from lib.ReadSettings import ParseXmlSettings
from lib.Info import Statistics, TypeTest
from lib.PyFarmExceptions import ErrorProcessingSetup

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
