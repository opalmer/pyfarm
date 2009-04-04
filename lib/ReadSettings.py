'''
AUTHOR: Oliver Palmer
HOMEPAGE: www.pyfarm.net
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
# From Python
from sys import exit
from os.path import isdir, isfile
from os import listdir
import xml.dom.minidom

# From PyQt
from PyQt4.QtCore import QRegExp

# From PyFarm
import Info

class SoftwareSearch(object):
    '''
    DESCRIPTION:
        Used to find and relay information about the currently installed
        software to the xml parser
    '''
    def __init__(self):
        self.modName = 'ReadSettings.SoftwareSearch'
        self.os = Info.System().os()[0]

    def _findProgram(self, expession, path, rendererSearch, expCapStart, programName):
        '''
        Given a regular expression and other information,
        search for and return all results.

        INPUT:
            expession -- regular expression to use in search
            path -- path to start search at
            expCapStart -- Position to return cap from
            rendererSearch -- final path to renderer (the file to search for)
        '''
        exp = QRegExp(expession) # declare the regular expression

        if self.os == 'linux' or self.os == 'mac':
            for result in listdir(path):
                if not exp.indexIn(result):
                    if isfile("%s/%s/%s" % (path, result, rendererSearch)):
                        yield [str('%s %s' % (programName, exp.cap(0)[expCapStart:])), \
                                   str("%s/%s/%s" % (path, result, rendererSearch))]

    def _maya(self, path, software):
        '''
        Run a search for  maya at the given path

        INPUT:
            path (str) -- path to searh for maya at
            software (str) -- common name of program to pass
        '''
        expression = r"""[m|M]aya(200[89]|8.[05]|8[05]|7(.0|0))"""
        if self.os == 'linux':
            for program in  self._findProgram(expression, path, 'bin/Render', 4, 'Maya'):
                yield [program[0], program[1], 'maya']

    def _houdini(self, path, software):
        '''
        Run a search for  houdini at the given path

        INPUT:
            path (str) -- path to searh for houdini at
            software (str) -- common name of program to pass
        '''
        OUTPUT = {}
        expression = r"""hfs9.[15].[0-9]+"""
        win_expression = r"""Houdini 9.[15].[0-9]+"""

    def _shake(self, path, software):
        '''
        Run a search for shake at the given path

        INPUT:
            path (str) -- path to searh for shake at
            software (str) -- common name of program to pass
        '''
        if isfile("%s/bin/shake" % path):
            return ['Shake', str("%s/bin/shake"% path), str(software)]

    def atLocation(self, path, software):
        '''
        Given a path and sofware run a search

        INPUT:
            path (str) -- path to search for software
            software (str) -- software to search for
        '''
        if isdir(path):
            print 'PyFarm :: %s :: Running search @ %s for %s' % (self.modName, path, software)
            if software == 'maya':
                for package in self._maya(path, software):
                    yield package
            elif software == 'houdini':
                self._houdini(path, software)
            elif software == 'shake':
                yield self._shake(path, software)
            else:
                exit('PyFarm :: %s :: ERROR :: %s is not a valid software package' % software)


class ParseXmlSettings(object):
    '''
    DESCRIPTION:
        Used to receieve, process, and return settings from an xml file
        to the main progam.

    INPUT:
        self.doc (str) -- The xml self.document to read from
    '''
    def __init__(self, doc, skipSoftware=False):
        self.modName = 'ReadSettings.XmlSettings'
        self.os = Info.System().os()[0]

        # check for xml formatting errors
        try:
            self.doc = xml.dom.minidom.parse(doc)
        except xml.parsers.expat.ExpatError, error:
            exit("PyFarm :: %s :: ERROR :: Could not parse xml file: %s" % (self.modName, error))

        # setup dictionaries
        self.netPorts = self._netPort()
        self.netGen = self._netGeneral()
        self.statusKeyDict = self._setJobStatusDict()

        self.jobStatusDict = None
        self.hostStatusDict = None

        if not skipSoftware:
            # setup general software setting
            softwareGen = self._softwareGeneral()
            self.softwareWidgetIndex = softwareGen[0]
            self.softwareFileGrep = softwareGen[1]

            # gather the currently installed software and add it
            #  to the software dictionary
            self.softwareList = []
            self.softwareCommand = {}
            self.softwareCommonName = {}
            for package in self._installedSoftware():
                self.softwareList.append(package[0])
                self.softwareCommand[package[0]] = package[1]
                self.softwareCommonName[package[0]] = package[2]

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
        software_installed = SoftwareSearch()
        for parent in self._getElement(self.doc, 'software'):
            for search in self._getElement(parent, 'search'):
                if search.getAttribute('os') == self.os:
                    for path in self._getElement(search, 'path'):
                        for searchPath in path.childNodes:
                            for result in software_installed.atLocation(searchPath.data, search.getAttribute('software')):
                                yield result

    def _softwareGeneral(self):
        '''
        Find the values for each software package and
        add it to the dictionary
        '''
        widgetIndexes = {}
        softwareFileGrep = {}
        for parent in self._getElement(self.doc, 'software'):
            for maya in self._getElement(parent, 'maya'):
                widgetIndexes['maya'] = str(maya.getAttribute('widgetIndex'))
                softwareFileGrep['maya'] = str(maya.getAttribute('fileGrep'))

            for houdini in self._getElement(parent, 'houdini'):
                widgetIndexes['houdini'] = str(houdini.getAttribute('widgetIndex'))
                softwareFileGrep['houdini'] = str(houdini.getAttribute('fileGrep'))

            for shake in self._getElement(parent, 'shake'):
                widgetIndexes['shake'] = str(shake.getAttribute('widgetIndex'))
                softwareFileGrep['shake'] = str(shake.getAttribute('fileGrep'))

        return [widgetIndexes, softwareFileGrep]

    def _statusKeyGenerator(self):
        for parent in self._getElement(self.doc, 'settings'):
            for child in self._getElement(parent, 'status'):
                yield child

    def _setJobStatusDict(self):
        '''
        Discover the status key entries in the xml file and
        add them to the dictionary
        '''
        statusDict = {}
        for child in self._statusKeyGenerator():
            for node in self._getElement(child, 'job'):
                for status in child.childNodes:
                    if status.nodeType== 1:
                        print status
                        #statusDict[int(status.getAttribute('index'))] = str(status.getAttribute('text'))

        return statusDict

    def _setHostStatusDict(self):
        statusDict = {}
        for child in self._statusKeyGenerator():
            for node in self._getElement(child, 'hosts'):
                for status in child.childNodes:
                    if status.nodeType== 1:
                        statusDict[int(status.getAttribute('index'))] = str(status.getAttribute('text'))

        return statusDict

    def netPort(self, service):
        '''
        Return the port for the given service

        INPUT:
            service (str) -- service to return a listing for
        '''
        try:
            return self.netPorts[service]
        except KeyError, key:
            exit('PyFarm :: %s :: ERROR:: %s is not a valid key in netPorts' % (self.modName, key))

    def netGeneral(self, setting):
        '''
        Return the value assoicated with the given setting

        INPUT:
            settings (str) -- the setting to search for
        '''
        try:
            if setting == 'unit16':
                return int(self.netGen[setting])
            else:
                return self.netGen[setting]
        except KeyError, key:
            exit('PyFarm :: %s :: ERROR:: %s is not a valid key in netGen' % (self.modName, key))

    def installedSoftware(self):
        '''Return a list of the currently installed software'''
        return self.softwareList

    def command(self, software):
        '''
        Return the command assoicated with the given software

        INPUT:
            software (str) -- the software to retrieve the command for
        '''
        try:
            return self.softwareCommand[software]
        except KeyError, key:
            exit('PyFarm :: %s :: ERROR:: %s is not a valid key in softwareCommand'% (self.modName, key))

    def commandList(self):
        '''Return the full list of render commands'''
        return self.softwareCommand

    def commonName(self, software):
        '''
        Return the common name of the given software

        INPUT:
            software (str) -- the software to retrieve the common name for
        '''
        try:
            return self.softwareCommonName[software]
        except KeyError, key:
            exit('PyFarm :: %s :: ERROR:: %s is not a valid key in softwareCommonName'% (self.modName, key))

    def widgetIndex(self, software):
        '''
       Return the index of the widget for the given software

        INPUT:
            software (str) -- software to index for (using common name)
        '''
        try:
            return int(self.softwareWidgetIndex[self.commonName(software)])
        except KeyError, key:
            exit('PyFarm :: %s :: ERROR:: %s is not a valid key in softwareWidgetIndex' % (self.modName, key))

    def fileGrep(self, software):
        '''
       Return the index of the widget for the given software

        INPUT:
            software (str) -- software to index for (using common name)
        '''
        try:
            return self.softwareFileGrep[self.commonName(software)]
        except KeyError, key:
            exit('PyFarm :: %s :: ERROR:: %s is not a valid key in softwareFileGrep' % (self.modName, key))

    def frameStatusKey(self, key):
        '''Return the status string for a given key'''
        try:
            return self.statusKeyDict[key]
        except KeyError, key:
            exit('PyFarm :: %s :: ERROR:: %s is not a valid key in statusKeyDict' % (self.modName, str(key)))

    def hostStatusKey(self, key):
        '''Return the status string for a given key'''
        try:
            return self.statusKeyDict[key]
        except KeyError, key:
            exit('PyFarm :: %s :: ERROR:: %s is not a valid key in statusKeyDict' % (self.modName, str(key)))
