'''
HOMEPAGE: www.pyfarm.net
INITIAL: Sept 26 2009
PURPOSE: Database module for inserting data

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
from DBMain import DBConnection

class InsertFrame(object):
    '''
    Function used to insert a givent frame into the database

    DEFAULTS:
        sTime -- 0
        eTime -- 0
        status -- 0
        logid -- 0
    '''
    def __init__(self, db):
        self.db = DBConnection(db)

    def setFrame(self, frame):
        '''Set the frame number'''
        pass

    def setJob(self, job):
        '''Set the job name for the frame'''
        pass

    def setSubjob(self, subjob):
        '''Set the subjob id'''
        pass

    def setCommand(self, command):
        '''Set the command for the frame'''
        pass
