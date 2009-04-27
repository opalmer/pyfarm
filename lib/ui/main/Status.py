'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2009
PURPOSE: To manage the status subsection of PyFarm

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
from PyQt4.QtCore import QString

class PyFarm(object):
    '''Manager for the PyFarm status section'''
    def __init__(self, ui):
        self.ui = ui

    def getText(self, num):
        '''Return the correct text for the given input num'''
        if num:
            return '<font color="green">Active</font>'
        else:
            return '<font color="red">Inactive</font>'

    def setMaster(self, state):
        '''Set the state and color of the master text'''
        self.ui.status_pyfarm_master.setText(self.getText(state))

    def setNetwork(self, state):
        '''Set the state and color of the network text'''
        self.ui.status_pyfarm_network.setText(self.getText(state))

    def setQueue(self, statue):
        '''Set the state and color of the queue text'''
        self.ui.status_pyfarm_que.setText(self.getText(state))


class Network(object):
    '''Manager for the Network status section'''
    def __init__(self, ui, data):
        self.ui = ui
        self.data = data

    def getColor(self, value):
        '''Return a color for a given value'''
        if value:
            return "green"
        else:
            return "red"

    def refreshConnected(self):
        '''Refresh the number of connected hosts'''
        count = self.data.network.hostCount()
        txt = '<font color=%s>%s</font>' % (self.getColor(count), count)
        self.ui.status_network_connected.setText(txt)

    def setStatus(self, ip, status):
        '''Set the status for the given host'''
        pass


class System(object):
    '''Manager for Queue.System status info'''
    def __init__(self, ui):
        self.ui = ui


class Jobs(object):
    '''Manager for Queue.Jobs status info'''
    def __init__(self, ui):
        self.ui = ui


class Frames(object):
    '''Manager for Queue.Frames status info'''
    def __init__(self, ui):
        self.ui = ui

    def addWaiting(self):
        '''Increase the waiting frame count'''
        self.ui.status_que_frames_waiting.setNum(int(self.ui.status_que_frames_waiting.text())+1)


class General(object):
    '''Manager for the general status objects'''
    def __init__(self, ui):
        self.ui = ui


class QueueManager(object):
    '''Manager for queue status objects'''
    def __init__(self, ui):
        self.ui = ui
        self.system = System(self.ui)
        self.jobs = Jobs(self.ui)
        self.frames = Frames(self.ui)


class StatusManager(object):
    '''General status manger'''
    def __init__(self, data, ui, version=None):
        self.ui = ui
        self.pyfarm = PyFarm(self.ui)
        self.network = Network(self.ui, data)
        self.queue = QueueManager(self.ui)
        self.general = General(self.ui)

        # general setup
        if version != None:
            self.ui.status_general_version.setText(version)
