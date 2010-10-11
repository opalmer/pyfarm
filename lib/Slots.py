'''
HOMEPAGE: www.pyfarm.net
INITIAL: July 17 2010
PURPOSE: Slot library for quick access to small actions

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

from lib.net.udp.Broadcast import BroadcastSender

class Help(object):
    '''Slot functions from the help menu'''
    def __init__(self,parent, config):
        self.parent = parent
        self.ui = parent.ui
        self.config = config


class Host(object):
    '''Host related slot functions'''
    def __init__(self, parent, config):
        self.parent = parent
        self.ui = parent.ui
        self.config = config

    def find(self):
        '''Search for hosts running the client program'''
        self.broadcast = BroadcastSender(self.config)
        self.broadcast.start()


class Slots(object):
    '''Main slots object, all other slots are referenced from here'''
    def __init__(self, parent, config):
        self.help = Help(parent, config)
        self.host = Host(parent, config)