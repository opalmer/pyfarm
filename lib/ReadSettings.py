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
import sys
import xml.dom.minidom

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

class ParseXmlSettings(object):
    '''
    DESCRIPTION:
        Used to receieve, process, and return settings from an xml file
        to the main progam.

    INPUT:
        self.doc (str) -- The xml self.document to read from
    '''
    def __init__(self, doc):
        self.doc = xml.dom.minidom.parse(doc)
        self.modName = 'ReadSettings.XmlSettings'
        self.os = Info.System().os()[0]
        self.netPorts = self._netPort()
        self.netGen = self._netGeneral()
        self.software = self._installedSoftware()

    def _getElement(self, parent, tag):
        '''Yield all elements from parent given a tagName'''
        for child in parent.getElementsByTagName(tag):
            yield child

    def _netPort(self):
        '''Return a dictionary with the server name and port'''
        portList = {}
        for parent in self._getElement(self.doc, 'settings'):
            for setting in self._getElement(parent, 'network'):
                for node in self._getElement(setting, 'server'):
                    portList[str(node.getAttribute('type'))] = int(node.getAttribute('port'))
        return portList

    def _netGeneral(self):
        '''Return a dictionary with the general network settings'''
        netGeneral = {}
        for parent in self._getElement(self.doc, 'settings'):
            for setting in self._getElement(parent, 'network'):
                for node in self._getElement(setting, 'general'):
                    netGeneral[str(node.getAttribute('type'))] = str(node.getAttribute('value'))
        return netGeneral

    def _installedSoftware(self):
        '''
        Find the currently installed software and add it
        to the software dictionary
        '''
        # THIS FUNCTION STILL NEEDS TO BE WRITTEN
        # THIS FUNCTION STILL NEEDS TO BE WRITTEN
        # THIS FUNCTION STILL NEEDS TO BE WRITTEN
        # THIS FUNCTION STILL NEEDS TO BE WRITTEN
        #software_installed = SoftwareSearch()
        software = {}
        for parent in self._getElement(self.doc, 'software'):
            for search in self._getElement(parent, 'search'):
                if search.getAttribute('os') == self.os:
                    for path in self._getElement(search, 'path'):
                        for searchPath in path.childNodes:
                            # THIS FUNCTION STILL NEEDS TO BE WRITTEN
                            # software_install.atLocation(searchPath.data, search.getAttribute('software'))
                            print searchPath.data, search.getAttribute('software')


    def netPort(self, service):
        '''
        Return the port for the given service

        INPUT:
            service (str) -- service to return a listing for
        '''
        return self.netPorts[service]

    def netGeneral(self, setting):
        '''
        Return the value assoicated with the given setting

        INPUT:
            settings (str) -- the setting to search for
        '''
        if setting == 'unit16':
            return int(self.netGen[setting])
        else:
            return self.netGen[setting]

    def installedSoftware(self):
        '''Return a dictionary of the currently installed software'''
        return self.software
