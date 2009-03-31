#!/usr/bin/python
'''
PURPOSE: Test of subclassing

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
class Job(dict):
    def __init__(self, job):
        self.job = {"job": {"out":job}}
        dict.__init__(self, self.job)

    def __getattr__(self, item):
        return self.__getitem__(item)

    def __setattr__(self, item, value):
        self.__setitem__(item, value)

    def setNew(self):
        self.job = {"out" :"new"}

'''
TRY A DECORATOR (IS THAT THE RIGHT WORD?? THAT RE-RUNS THE INITIALIZATION
OF THE DICTIONARY
'''
a = Job('hell2o')
a.setNew()
print a["job"]
print a["out"]
