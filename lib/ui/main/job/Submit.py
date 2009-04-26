'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 12 2009
PURPOSE: Contains classes related to initial job submission

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
from os.path import isfile
from pprint import pprint

# From PyFarm
from lib import Info
from lib.data.Job import JobManager
from lib.ReadSettings import ParseXmlSettings
from lib.RenderConfig import ConfigureCommand
from lib.ui.main.CustomWidgets import MessageBox
from lib.Distribute import DistributeFrames

settings = ParseXmlSettings('%s/settings.xml' % getcwd())

class SubmitManager(object):
    '''
    Handels the initial request from the gui

    INPUT:
        ui -- user interface instance
        dataJob -- job data dictionary
        dataGeneral -- general data manager class instance
    '''
    def __init__(self, parentClass):
        self.ui = parentClass.ui
        self.jobs = parentClass.dataJob
        self.dataGeneral = parentClass.dataGeneral
        self.softwareManager = parentClass.softwareManager
        self.msg = MessageBox(parentClass)
        self.rendering = 0
        self.tableManager = parentClass.tableManager

    def submitJob(self):
        '''
        Begin processing information from the
        user interface
        '''
        priority = self.ui.inputJobPriority.value()
        jobName = str(self.ui.inputJobName.text())
        jobID = self.runChecks()

        # if the job has not been defined, define it
        if jobName not in self.jobs:
            self.jobs[jobName] = JobManager(jobName, self)
            self.tableManager.addJob(jobName)

        if jobID:
            self.createJob(jobName, jobID, priority)

    def createJob(self, jobName, id, priority):
        '''
        Create a job and add it to the dictionary

        INPUT:
            jobName (str) -- name of job
            id (hex) -- subjob id
        '''
        if self.jobs[jobName].data.createSubjob(id, priority):
            for frame in range(self.ui.inputStartFrame.value(), self.ui.inputEndFrame.value()+1, self.ui.inputByFrame.value()):
                self.jobs[jobName].data.addFrame(id, frame, str(self.ui.softwareSelection.currentText()))
        else:
            self.msg.warning("Please Wait Before Submitting Another Job",
                             "You must wait at least two seconds before submitting another job.")

    def runChecks(self):
        '''Check to be sure the user has entered the minium values'''
        scene = self.softwareManager.scene.text()
        if scene == '':
            self.msg.warning('Missing File', 'You must provide a file to render')
            return 0
        elif not isfile(scene):
            self.msg.warning('Please Select a File', 'You must provide a file to render.')
            return 0
        else:
            try:
                if self.ui.inputJobName.text() == '':
                    self.msg.warning('Missing Job Name', 'You name your job before you rendering')
                    return 0
            except AttributeError:
                self.msg.warning('Missing Job Name', 'You name your job before you rendering')
                return 0
            finally:
                # get a random number and return the hexadecimal value
                return Info.Numbers().hexid()

    def startRender(self):
        '''Start the que and begin rendering'''
        #print "Checking software"
        if len(self.jobs):
            render = DistributeFrames(self)
        else:
            self.msg.warning("Please Submit A Job",  "You must submit a job before attempting to render")
