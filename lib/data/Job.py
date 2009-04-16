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

# From PyFarm
from lib.ReadSettings import ParseXmlSettings
from lib.Info import Statistics, TypeTest
from lib.PyFarmExceptions import ErrorProcessingSetup
from lib.RenderConfig import ConfigureCommand

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


class JobData(object):
    '''
    Add or modify a job

    INPUT:
        job (mapped class) -- job dictionary to work with
        name (str) - job name of data
        generalData (mapped class) -- instance of general class that contains
        program wide information
    '''
    def __init__(self, job, name, generalData, ui, parent):
        self.job = job
        self.name = name
        self.general = generalData
        self.parent = parent
        self.modname = 'JobData'
        self.renderConfig = ConfigureCommand(ui)
        self.ui = ui

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
                                            "paused" : 0
                                            }},
                                    "frames" : {}
                                    }
            return True
        else:
            return False

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
                    command = self.renderConfig.maya(frame, layer.text())
            else:
                command = self.renderConfig.maya(frame)
        elif settings.commonName(str(self.ui.softwareSelection.currentText())) == 'houdini':
            print "houdini"
        elif settings.commonName(str(self.ui.softwareSelection.currentText())) == 'shake':
            print "shake"

        entry = {"status" : 0, "host" : None,
                        "pid" : None, "software" : software,
                        "start" : None, "end" : None, "elapsed" : None,
                        "command" : command
                        }

        # add the frame to the frames dictionary
        self.job[id]["frames"].update({frame : entry})

        # update the subjob statistics
        self.job[id]["statistics"]["frames"]["frameCount"] += 1
        self.job[id]["statistics"]["frames"]["waiting"] += 1


class JobManager(object):
    '''
    General job manager used to manage and
    setup a job
    '''
    def __init__(self, name, generalData, ui):
        self.ui = ui
        self.name = name
        self.general = generalData
        self.job = {}
        self.data = JobData(self.job, self.name, self.general, ui, self)
        self.status = JobStatus(self.job, self.name, self.general, ui, self)

    def jobData(self):
        '''Return the dictionary, for previewing'''
        return self.job
