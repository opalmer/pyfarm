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
        self.software = {}
        self.msg = parentClass.msg
        self.jobs = parentClass.jobs
        self.data = parentClass.dataGeneral
        self.hosts = parentClass.dataGeneral.dataGeneral()["network"]["hosts"]
        self.hostList = self.hosts.keys()
        self.priority = 1

        # run setup functions
        self.indexSoftware()

    def sendFrames(self):
        '''Send the first frames out to the remote clients'''
        for host in self.hostList:
            for job in self.jobs:
                self.getFrame(job)

    def getFrame(self, job):
        '''Get a frame from the job dictionary'''
        for frame in self.jobs[job].yieldFrames('waiting'):
            sjob = frame[0]
            num = frame[1]
            status = frame[2]["status"]
            self.jobs[job].status.setFrame(sjob, num, 1)

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
        for host in self.hostList:
            self.software[host] = []
            for package in self.hosts[host]["software"].values():
                for software in package.keys():
                    self.software[host].append(software)
