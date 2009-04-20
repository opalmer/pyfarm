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
from pprint import pprint

# From PyFarm
from lib.ReadSettings import ParseXmlSettings
from lib.Info import Statistics, TypeTest
from lib.PyFarmExceptions import ErrorProcessingSetup
from lib.RenderConfig import ConfigureCommand
from lib.ui.main.Status import StatusManager

settings = ParseXmlSettings('%s/settings.xml' % getcwd())
statistics = Statistics()
typeCheck = TypeTest('JobData')
error = ErrorProcessingSetup('JobData')

class JobStatus(object):
    '''
    Return status information about a job

    INPUT:
        job (dict) -- job dictionary to work with
        name (str) -- job name
        generalData (mapped class) -- instance of general class that contains
        program wide information
    '''
    def __init__(self, job, name, generalData, ui, parent):
        self.job = job
        self.name = name
        self.general = generalData
        self.jobManager = parent
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

    def subjob(self, id):
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
                for entry in self.job[id]["frames"][frame]:
                    yield [subjob, frame, entry]
        else:
            for subjob in self.listSubjobs():
                for frame in self.job[subjob]["frames"].keys():
                    for entry in self.job[subjob]["frames"][frame]:
                        yield [subjob, frame, entry]

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

    def waitingFrameCount(self):
        '''Return the number of waiting frames'''
        count = 0
        for id in self.job.keys():
            if id != None:
                count += self.job[id]["statistics"]["frames"]["waiting"]
        return count

    def renderingFrameCount(self):
        '''Return the number of rendering frames'''
        count = 0
        for id in self.job.keys():
            if id != None:
                count += self.job[id]["statistics"]["frames"]["rendering"]
        return count

    def failedFrameCount(self):
        '''Return the number of failed frames'''
        count = 0
        for id in self.job.keys():
            if id != None:
                count += self.job[id]["statistics"]["frames"]["failed"]
        return count

    def completeFrameCount(self):
        '''Return the number of complete frames'''
        count = 0
        for id in self.job.keys():
            if id != None:
                count += self.job[id]["statistics"]["frames"]["complete"]
        return count

    def subjobCount(self):
        '''Return the subjob count'''
        return len(self.job.keys())


class JobData(object):
    '''
    Add or modify a job

    INPUT:
        job (dict) -- job dictionary to work with
        name (str) - job name of data
        parentClass (mapped class) -- instance of main program
        jobManager (mapped class) -- instance of job manager
    '''
    def __init__(self, job, name, parentClass, jobManager):
        self.job = job
        self.name = name
        self.general = parentClass.dataGeneral
        self.parent = parentClass
        self.modname = 'JobData'
        self.renderConfig = ConfigureCommand(parentClass.ui)
        self.ui =parentClass.ui
        self.jobManager = jobManager

    def createSubjob(self, id, priority):
        '''
        Create a sub job

        INPUT:
            id (str) -- subjob id to create
            priority (int) -- priority to give job
        '''
        if id not in self.job:
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
                                            "complete" : 0
                                            }},
                                    "frames" : {}
                                    }
            return True
        else:
            return False

    def _addEntry(self, id, frame, software, command):
        '''
        Add an entry to a subjob.  This function was created to
        help keep cleaner code.

        INPUT:
            id -- subjob id
            software -- software to render with
            command -- command to render with
        '''
        # create the dictionary entry
        entry = {"status" : 0, "host" : None,
            "pid" : None, "software" : software,
            "start" : None, "end" : None, "elapsed" : None,
            "command" : str(command)
            }

        # add the frame to the frames dictionary
        if frame not in self.job[id]["frames"]:
            self.job[id]["frames"][frame] = [["%x" % self.job[id]["statistics"]["frames"]["frameCount"], entry]]
        else:
            self.job[id]["frames"][frame].append(["%x" % self.job[id]["statistics"]["frames"]["frameCount"], entry])

        # update the subjob statistics
        self.job[id]["statistics"]["frames"]["frameCount"] += 1
        self.job[id]["statistics"]["frames"]["waiting"] += 1
        self.jobManager.uiStatus.queue.frames.addWaiting()
        self.parent.tableManager.addFrame(self.name)

    def addFrame(self, id, frame, software):
        '''
        add a frame to the job

        INPUT:
            id (str) --  subjob to add the frame to
            frame (int) --  frame number
            software (str) -- software package to render with
        '''
        if settings.commonName(str(self.ui.softwareSelection.currentText())) == 'maya':
            if len(self.ui.mayaRenderLayers.selectedIndexes()):
                for layer in self.ui.mayaRenderLayers.selectedItems():
                    self._addEntry(id, frame, software, self.renderConfig.maya(frame, layer.text()))
            else:
                self._addEntry(id, frame, software, self.renderConfig.maya(frame))
        elif settings.commonName(str(self.ui.softwareSelection.currentText())) == 'houdini':
            print "houdini"
        elif settings.commonName(str(self.ui.softwareSelection.currentText())) == 'shake':
            print "shake"


class JobManager(object):
    '''
    General job manager used to manage and
    setup a job
    '''
    def __init__(self, name, parentClass):
        self.job = {}
        self.data = JobData(self.job, name, parentClass, self)
        self.status = JobStatus(self.job, name, parentClass.dataGeneral, parentClass.ui, self)
        self.uiStatus = StatusManager(parentClass.dataGeneral, parentClass.ui)

    def jobData(self):
        '''Return the dictionary, for previewing'''
        return self.job
