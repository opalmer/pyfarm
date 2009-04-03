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
from sys import exit
from os import getcwd
from PyQt4.QtCore import *
from ReadSettings import ParseXmlSettings
from Info import Numbers

settings = ParseXmlSettings('%s/settings.xml' % getcwd(), skipSoftware=True)
numbers = Numbers()

class JobStatus(QObject):
    '''
    Return status information about a job

    INPUT:
        job (dict) -- job dictionary
    '''
    def __init__(self, job, name, parent=None):
        super(JobStatus, self).__init__(parent)
        self.job = job
        self.name = name
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
            subjobs.append()
        return numbers.mode()

    def subjob(self, id, emitSignal=False):
        '''Return the status of a subjob'''
        statusList = []
        for frame in self.job[id]["frames"].keys():
            statusList.append(self.frame(id, frame))

        # get mode, set subjob status,
        status = numbers.mode(statusList)
        self.setSubjob(id, status)
        return status

    def setSubjob(self, id, status):
        '''
        Set the status of the given subjob

        INPUT:
            id (str) [0-3] -- subjob id
        '''
        if status >= 0 and status <= 3:
            self.job[id]["status"] = status
            self.parent.emitSignal("subjobStatusChanged", [self.name, id, status])
        else:
            exit("PyFarm :: %s.setSubjob :: Expected a value >= 0 and <= 3") % self.modName

    def frame(self, id, frame, emitSignal=False):
        '''Return the status of a frame'''
        return self.job[id][frame]["status"]

    def setFrame(self, id, frame):
        '''
        Set the status of the given frame

        INPUT:
            id (str) -- subjob id
            frame (int) -- frame number to set
        '''
        pass

    def listSubjobs(self):
        '''List the subjobs contained in self.job'''
        return self.job.keys()

    def listFrames(self, id=False):
        '''
        If id is given, list the frames of the id.  If
        id is not given, list ALL frames.
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
    '''
    def __init__(self, job, name, parent=None):
        super(JobData, self).__init__(parent)
        self.job = job
        self.name = name
        self.parent = parent
        self.modname = 'JobData'

    def createSubjob(self, id, priority=5):
        '''
        Create a sub job

        INPUT:
            id (str) -- subjob id to create
        '''
        if priority >= 1 and priority <= 10:
            self.job[id] = {"priority" : priority, "status" : 0, "frames" : {}}
        else:
            exit('PyFarm :: %s.createSubjob :: ERROR :: Priority must be <= 10 and >= 1')

    def addFrame(self, id, frame, software, command):
        '''
        add a frame to the job

        INPUT:
            subid (str) --  subjob to add the frame to
            frame (int) --  frame number
            software (str) -- software packagee to render with
            command (str) -- command to render with
        '''
        entry = {"status" : 0, "host" : None,
                        "pid" : 0, "software" : software,
                        "command" : command
                        }
        # add the frame to the frames dictionary
        self.job[id]["frames"].update({frame : entry})

        # inform the gui of the new frame
        #self.emit(SIGNAL("newFrame"), [id, frame, entry])
        self.parent.emitSignal("newFrame", [self.name, id, frame, entry])


class JobManager(QObject):
    '''
    General job manager used to manage and
    setup a job
    '''
    def __init__(self, name, parent=None):
        super(JobManager, self).__init__(parent)
        self.name = name
        self.job = {}
        self.data = JobData(self.job, self.name, self)
        self.status = JobStatus(self.job, self.name, self)

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


if __name__ == '__main__':
    class Main(QObject):
        '''random test code'''
        def __init__(self, parent=None):
            super(Main, self).__init__(parent)

        def execute(self):
            jobNames = ["job1", "job2"]
            subjobs = ["sbj1", "sbj2"]
            jobs = {}

            # create jobs, connect their signals/slots, create subjobs, add frames
            for jobName in jobNames:
                jobs.update({jobName : JobManager(jobName)})
                self.setupSignals(jobs[jobName])
                for subjob in subjobs:
                    jobs[jobName].data.createSubjob(subjob)

                for subjob in jobs[jobName].status.listSubjobs():
                    for frame in range(1, randint(3, 13)):
                        jobs[jobName].data.addFrame(subjob, frame, "Maya 2009", "render -r mr -v 5 scene.mb")

            # List the subjobs for each job
            for job in jobs.keys():
                print "\n%s has %i subjobs and %i frames" % (job, jobs[job].status.subjobCount(), jobs[job].status.frameCount())
                for subjob in jobs[job].status.listSubjobs():
                    jobs[job].status.setSubjob(subjob, randint(0, 3))
                    print "\tsubjob %s:" % subjob
                    print "\t\tFrames: %i\n\t\tStatus: %s" % (jobs[job].status.frameCount(subjob), settings.statusKey(jobs[job].status.subjob(subjob)))

                jobs[job].status.overall()
                #print "\t\tStatus: %s" % jobs[job].status.overall()

        def setupSignals(self, jobName):
            sigSlots = {'newFrame': self.newFrameAdded, 'subjobStatusChanged': self.subjobStatusChanged}

            for sig, slt in sigSlots.items():
                self.connect(jobName, SIGNAL(sig), slt)

        def newFrameAdded(self, info):
            job = info[0]
            id = info[1]
            frame = info[2]
            entry = info [3]
            #print "Subjob %s from %s has added frame %i" % (id, job, frame)

        def subjobStatusChanged(self, info):
            job = info[0]
            id = info[1]
            status = info[2]
            #print "Subjob %s from %s has changed status to %s" % (id, job, settings.statusKey(status))

    from Info import Numbers
    from pprint import pprint
    from sys import argv, exit
    from random import randint

    app = QCoreApplication(argv)
    main = Main()
    exit(main.execute())
    app.exec_()
