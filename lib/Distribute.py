'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 26 2009
PURPOSE: Module used to distribute a render and act as an in between
for the interface and the network.

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
from pprint import pprint

# From PyFarm
from lib.network.Que import QueClient

class DistributeFrames(object):
    '''Setup the que and gather the required data'''
    def __init__(self, parentClass):
        self.modName = 'DistributeFrames'
        self.software = {}
        self.msg = parentClass.msg
        self.jobs = parentClass.jobs
        self.data = parentClass.dataGeneral
        self.network = parentClass.dataGeneral.dataGeneral()["network"]["hosts"]
        self.hosts = self.network.keys()
        self.priority = 1

        # run setup functions
        self.indexSoftware()

    def sendFrames(self):
        '''Send the first frames out to the remote clients'''
        hostCount = len(self.hosts)
        if hostCount > 0:
            for host in self.hosts:
                for job in self.jobs:
                    frame = self.getFrame(host, job)
                    if frame: # check to make sure we found a frame
                        data = {
                                "job" : job,
                                "subjob" : frame[0],
                                "frameNum" : frame[1],
                                "frameID" : frame[2][0],
                                "software" : frame[2][1]["software"],
                                "command" : frame[2][1]["command"]
                                }
                        #client = QueClient(host, self)
                        #client.issueRequest(job, frame[0],)
                        self.jobs[job].data.frame.setHost(data["subjob"], data["frameNum"], data["frameID"], host)
                        self.jobs[job].data.frame.setStart(data["subjob"], data["frameNum"], data["frameID"])
                        self.jobs[job].data.frame.setEnd(data["subjob"], data["frameNum"], data["frameID"])
        else:
            self.msg.warning('Hosts Not Connected', 'Before rendering you must have at least one host connected to the network.')


    def getFrame(self, host, job):
        '''Get a frame from the job dictionary'''
        # if the host is in a waiting state
        if self.network[host]["status"] == 0:
            frame = self.jobs[job].getFrame()
            if frame: # ONLY if we find a frame should we continue
                # inform the user of the node, then set its status
                print "PyFarm :: %s :: %s is avaliable to render" % (self.modName, host)
                #print frame
                # set the frame and host status
                self.data.network.host.setStatus(host, 1)
                self.jobs[job].status.setFrame(frame[0], frame[1], 1)

                return frame
            else:
                return 0


    def hasSoftware(self, host, software):
        '''Check and see if the given host has the software installed'''
        if software in self.software[host]:
            return True
        else:
            return False

    def sendFrame(self, ip):
        '''Send an individual frame to the given ip'''
        client = QueClient(ip)

    def indexSoftware(self):
        '''
        So we dont have to ask each time, create a software index
        dictionary for each host.
        '''
        for host in self.network:
            self.software[host] = []
            for package in self.network[host]["software"].values():
                for software in package.keys():
                    self.software[host].append(software)
