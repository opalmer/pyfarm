'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Jan 1 2009
PURPOSE: Used to read in settings of PyFarm

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

COMMENT = '#'

def isMatch(line, search):
    '''
    Returs the value of the given line if
    the requested search matches
    '''
    lineSplit = line.split('::')
    output = ''
    if lineSplit[0] == search:
        return lineSplit[1]

def getValue(openFile,search):
    '''Retrun the requested var'''
    while 1:
            lines = file.readlines(openFile)
            if not lines:
                break

            for line in lines:
                if line.split('\n')[0].split('::')[0] == search:
                    return line.split('\n')[0].split('::')[1]
                else:
                    pass

class Network(object):
    '''Read and output the network related settings'''
    def __init__(self, settings_file):
        self.file = open(settings_file)
        self.keys = ['MASTER_ADDRESS','BROADCAST_PORT',\
                            'TCP_STD_OUT','TCP_STD_ERR',\
                            'TCP_QUE', 'SIZE_OF_UNIT16']

    def MasterAddress(self):
        '''Return the master's address'''
        return getValue(self.file, self.keys[0])

    def BroadcastPort(self):
        '''Return the port of broadcast coms'''
        return int(getValue(self.file, self.keys[1]))

    def StdOutPort(self):
        '''Return the standard output TCP port'''
        return int(getValue(self.file, self.keys[2]))

    def StdErrPort(self):
        '''Return the standard error TCP port'''
        return int(getValue(self.file, self.keys[3]))

    def QuePort(self):
        '''Return the Que TCP port'''
        return int(getValue(self.file, self.keys[4]))

    def Unit16Size(self):
        '''Return the size of a unit 16 packet'''
        return int(getValue(self.file, self.keys[5]))
