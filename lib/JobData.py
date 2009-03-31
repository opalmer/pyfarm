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
from Info import Numbers
from pprint import pprint

class SubJobFrame(object):
    def __init__(self, job, name):
        self.job = job
        self.name = name

    def status(self):
        print self.job


class Subjob(object):
    def __init__(self, job, name):
        self.job = job
        self.name = name
        self.frame = SubJobFrame(job, name)

    def listFrames(self):
        print self.job

    def new(self, id):
        self.job[id] = {}


class JobManager(object):
    '''
    DESCRIPTION:
        General job manager class, all other child classes
        are called from here.
    '''
    def __init__(self, name):
        self.job = {}
        self.name = name
        self.subjob = Subjob(self.job, name)

    def listSubJobs(self):
        return self.job.keys()

if __name__ == '__main__':
    jobNames = ["job1", "job2", "job3"]
    jobs = {}
    num = Numbers()

    for name in jobNames:
        jobs[name] = JobManager(name)
        for randint in range(1, num.randint(2, 7)):
            randhex = num.randhex(1000000, 2000000)
            jobs[name].subjob.new(randhex)

        pprint(jobs[name].listSubJobs())

    #job = Job()
    #job.subjob.new('ia31')
    #job.subjob.frames.status()
