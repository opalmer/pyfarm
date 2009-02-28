'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com
INITIAL: Jan 1 2009
PURPOSE: Used to read in settings of PyFarm

    This self.file is part of PyFarm.

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
import Info

SysInfo = Info.System()
COMMENT = '#'
KEY_SPLIT = '::'
PATH_SEP = ','
SETTINGS_FILE = Info.ModulePath(__file__, 1)+'settings.cfg'

# setup proper line termination
if SysInfo.os()[0] == 'windows':
    LINE_TERM = '\n'
else:
    LINE_TERM = '\n'

def CloseFile(openFile):
    '''
    Close the given open file instance
    '''
    openFile.close()

def isMatch(line, search):
    '''
    Returs the value of the given line if
    the requested search matches
    '''
    lineSplit = line.split(KEY_SPLIT)
    output = ''
    if lineSplit[0] == search:
        return lineSplit[1]

def getValue(openFile, search):
    '''
    Return the requested var

    INPUT:
        openFile -- File to searchin
        search -- line to search for
    '''
    while 1:
            lines = file.readlines(openFile)
            if not lines:
                CloseFile(openFile)
                break

            for line in lines:
                if line.split(LINE_TERM)[0].split(KEY_SPLIT)[0] == search:
                    return line.split(LINE_TERM)[0].split(KEY_SPLIT)[1]
                else:
                    CloseFile(openFile)

def getPaths(openFile, search):
    '''
    return all paths given by settings.cfg

    INPUT:
        openFile -- File to searchin
        search -- line to search for
    '''
    while 1:
        lines = file.readlines(openFile)
        if not lines:
            break

        for line in lines:
            if line.split(LINE_TERM)[0].split(KEY_SPLIT)[0] == search:
                if line.split(LINE_TERM)[0].split(KEY_SPLIT)[1] != 'NONE':
                    for newPath in line.split(LINE_TERM)[0].split(KEY_SPLIT)[1].split(PATH_SEP):
                        yield newPath
            else:
               pass

class Network(object):
    '''Read and output the network related settings'''
    def __init__(self):
        self.file = open(SETTINGS_FILE, 'r')

    def MasterAddress(self):
        '''Return the master's address'''
        return getValue(self.file, 'MASTER_ADDRESS')

    def BroadcastPort(self):
        '''Return the port of broadcast coms'''
        return int(getValue(self.file, 'BROADCAST_PORT'))

    def StdOutPort(self):
        '''Return the standard output TCP port'''
        return int(getValue(self.file, 'TCP_STD_OUT'))

    def StdErrPort(self):
        '''Return the standard error TCP port'''
        return int(getValue(self.file, 'TCP_STD_ERR'))

    def QuePort(self):
        '''Return the Que TCP port'''
        return int(getValue(self.file, 'TCP_QUE'))

    def Unit16Size(self):
        '''Return the size of a unit 16 packet'''
        return int(getValue(self.file, 'SIZE_OF_UNIT16'))


class SoftwareSearchPaths(object):
    '''Read and output the network related settings'''
    def __init__(self):
        self.file = open(SETTINGS_FILE, 'r')
        os_arch = SysInfo.os()
        self.os = os_arch[0]
        self.arch = os_arch[1]

    def Maya(self):
        '''Yield any extra paths that the user has given for maya'''
        if self.os == 'linux' and self.arch == 'x86':
            for path in getPaths(self.file, 'custom_path_maya_linux_x86'):
                yield path

        elif self.os == 'linux' and self.arch == 'x64':
            for path in getPaths(self.file, 'custom_path_maya_linux_x64'):
                yield path

        elif self.os == 'mac' and self.arch == 'x86':
            for path in getPaths(self.file, 'custom_path_maya_mac_x86'):
                yield path

        elif self.os == 'mac' and self.arch == 'x64':
            for path in getPaths(self.file, 'custom_path_maya_mac_x64'):
                yield path

        elif self.os == 'windows' and self.arch == 'x86':
            for path in getPaths(self.file, 'custom_path_maya_win_x86'):
                yield path

        elif self.os == 'windows' and self.arch == 'x64':
            for path in getPaths(self.file, 'custom_path_maya_win_x64'):
                yield path

    def Houdini(self):
        '''Yield any extra paths that the user has given for houdini'''
        if self.os == 'linux' and self.arch == 'x86':
            for path in getPaths(self.file, 'custom_path_maya_linux_x86'):
                yield path

        elif self.os == 'linux' and self.arch == 'x64':
            for path in getPaths(self.file, 'custom_path_houdini_linux_x64'):
                yield path

        elif self.os == 'mac' and self.arch == 'x86':
            for path in getPaths(self.file, 'custom_path_houdini_mac_x86'):
                yield path

        elif self.os == 'mac' and self.arch == 'x64':
            for path in getPaths(self.file, 'custom_path_houdini_mac_x64'):
                yield path

        elif self.os == 'windows' and self.arch == 'x86':
            for path in getPaths(self.file, 'custom_path_houdini_win_x86'):
                yield path

        elif self.os == 'windows' and self.arch == 'x64':
            for path in getPaths(self.file, 'custom_path_houdini_win_x64'):
                yield path


    def Shake(self):
        '''Yield any extra paths that the user has given for shake'''
        if self.os == 'linux':
            for path in getPaths(self.file, 'custom_path_shake_linux'):
                yield path

        elif self.os == 'mac':
            for path in getPaths(self.file, 'custom_path_shake_mac'):
                yield path
