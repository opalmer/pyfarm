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
# From PyFarm
from lib.network.Que import QueClient

class DistributeFrames(object):
    '''Setup the que and gather the required data'''
    def __init__(self, parentClass):
        self.msg = parentClass.msg
        self.jobs = parentClass.jobs
        self.data = parentClass.dataGeneral
        self.hosts = parentClass.dataGeneral.dataGeneral()["network"]["hosts"]
        self.ipList = self.hosts.keys()
        from pprint import pprint
        pprint(self.hosts)

    def sendFrame(self, ip):
        '''Send a frame to the given IP'''
        client = QueClient(ip)
