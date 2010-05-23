'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 1 2009
PURPOSE: Used to read in settings of PyFarm

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

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
import sys
import xml.dom.minidom
from os import listdir
from os.path import isdir, isfile

# From PyQt
from PyQt4.QtGui import QColor
from PyQt4.QtCore import QRegExp, QVariant

# From PyFarm
import Info
import lib.Logger as logger
from PyFarmExceptions import ErrorProcessingSetup

__MODULE__ = "lib.ReadSettings"

class SoftwareSearch(object):
    '''
    DESCRIPTION:
        Used to find and relay information about the currently installed
        software to the xml parser
    '''
    def __init__(self, logger, logLevels):
        self.modName = 'ReadSettings.SoftwareSearch'
        # logging setup
        self.logger = logger
        self.log = logger.moduleName("ReadSettings.SoftwareSearch")
        self.logLevels = logLevels
        self.os = Info.System(logger, logLevels).os()[0]
        self.log.debug("SoftwareSearch loaded")

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
                yield [program[0], program[1], software]

    def _houdini(self, path, software):
        '''
        Run a search for  houdini at the given path

        INPUT:
            path (str) -- path to searh for houdini at
            software (str) -- common name of program to pass
        '''
        OUTPUT = {}
        expression = r"""hfs([0-9]+[.][0-9][.][0-9]{3}[.]?[0-9]?)"""
        win_expression = r"""Houdini 9.[15].[0-9]+"""
        if self.os == 'linux':
            for program in  self._findProgram(expression, path, 'bin/hython', 3, 'Houdini'):
                yield [program[0], program[1], software]

    def _shake(self, path, software):
        '''
        Run a search for shake at the given path

        INPUT:
            path (str) -- path to searh for shake at
            software (str) -- common name of program to pass
        '''
        if isfile("%s/bin/shake" % path):
            return ['Shake', str("%s/bin/shake"% path), software]

    def atLocation(self, path, software):
        '''
        Given a path and sofware run a search

        INPUT:
            path (str) -- path to search for software
            software (str) -- software to search for
        '''
        if isdir(path):
            if software == 'maya':
                for package in self._maya(path, software):
                    yield package
            elif software == 'houdini':
                for package in self._houdini(path, software):
                    yield package
            elif software == 'shake':
                yield self._shake(path, software)


class ParseXmlSettings(object):
    '''
    DESCRIPTION:
        Used to receieve, process, and return settings from an xml file
        to the main progam.

    INPUT:
        self.doc (str) -- The xml self.document to read from
    '''
    def __init__(self, doc, type, skipSoftware, logger, logLevels):
        # old input vals: type='cmd', skipSoftware=False
        self.error = ErrorProcessingSetup('ReadSettings.XmlSettings')
        # logging setup
        self.logger = logger
        self.log = logger.moduleName("ReadSettings.ParseXmlSettings")
        self.logLevels = logLevels

        # check for xml formatting errors
        try:
            try:
                self.doc = xml.dom.minidom.parse(doc)
            except IOError:
                XMLFileNotFound(doc, type)

        except xml.parsers.expat.ExpatError, error:
            self.error.xmlFormattingError(doc, error)

        if type != 'log':
            self.os = Info.System(logger, logLevels).os()[0]
            # setup dictionaries
            self.netPorts = self._netPort()
            self.netGen = self._netGeneral()

            self.jobStatusDict = self._setStatusKeyDict()
            self.hostStatusDict = self._setHostStatusDict()
            self.boolColorDict = self._setBoolColor()
            self.broadcastDict = self._setBroadcastDict()
            self.bgColorDict = self._setBgColor()
            self.fgColorDict  = self._setFgColor()
            self.frameStatusDict = self._setFrameStatusDict()
            self.strFrameStatus, self.intFrameStatus = self._setStandardStatusDict()
            self._setupLog()

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
        else:
            self._setupLog()
        self.log.debug("ParseXmlSettings loaded")

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
        software_installed = SoftwareSearch(self.logger, self.logLevels)
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

    def _setHostStatusDict(self):
        '''Sets up the host status text, index, and color'''
        statusDict = {}
        index = 0
        for child in self._statusKeyGenerator():
            for node in self._getElement(child, 'hosts'):
                for status in node.childNodes:
                    if status.nodeType== 1:
                        statusDict[index] = str(status.getAttribute('text'))
                        index += 1

        return statusDict

    def _setBoolColor(self):
        '''Sets up the boolean color lookup'''
        boolColor = {}
        for parent in self._getElement(self.doc, 'settings'):
            for setting in self._getElement(parent, 'status'):
                for node in self._getElement(setting, 'general'):
                    for child in node.childNodes:
                        if child.localName == 'zeroValue':
                            boolColor[0] = [child.getAttribute('bgColor'), child.getAttribute('txtColor')]
                        elif child.localName == 'nonZeroValue':
                            boolColor[1] = [child.getAttribute('bgColor'), child.getAttribute('txtColor')]
                        else:
                            pass

    def _setBgColor(self):
        '''Set the bg color dictionary'''
        output = {}
        index = 0
        for parent in self._getElement(self.doc, 'settings'):
            for node in self._getElement(parent, 'status'):
                for children in self._getElement(node, 'job'):
                    for child in children.childNodes:
                        if child.nodeType == 1:
                            color = child.getAttribute('bgColor').split(',')
                            output[index] = QVariant(QColor(int(color[0]), int(color[1]), int(color[2])))
                            index += 1
        return output

    def _setFgColor(self):
        '''Set the fg color dictionary'''
        output = {}
        index = 0
        for parent in self._getElement(self.doc, 'settings'):
            for node in self._getElement(parent, 'status'):
                for children in self._getElement(node, 'job'):
                    for child in children.childNodes:
                        if child.nodeType == 1:
                            color = child.getAttribute('txtColor').split(',')
                            output[index] = QVariant(QColor(int(color[0]), int(color[1]), int(color[2])))
                            index += 1
        return output

    def _setFrameStatusDict(self):
        '''Set the fg color dictionary'''
        output = {}
        index = 0
        for parent in self._getElement(self.doc, 'settings'):
            for node in self._getElement(parent, 'status'):
                for children in self._getElement(node, 'job'):
                    for child in children.childNodes:
                        if child.nodeType == 1:
                            output[index] = QVariant(child.getAttribute('text'))
                            index += 1
        return output

    def _setStatusKeyDict(self):
        '''Setup the status key dictionary'''
        statusDict = {}
        index = 0
        for parent in self._getElement(self.doc, 'settings'):
            for status in self._getElement(parent, 'status'):
                for child in status.childNodes:
                    if child.localName == 'job':
                        for grandchild in child.childNodes:
                            if grandchild.nodeType == 1:
                                statusDict[index] = str(grandchild.getAttribute('text'))
                                index += 1
        return statusDict

    def netPort(self, service):
        '''
        Return the port for the given service

        INPUT:
            service (str) -- service to return a listing for
        '''
        try:
            return self.netPorts[service]
        except KeyError:
            raise self.error.xmlKeyError(service)

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
        except KeyError:
            raise self.error.xmlKeyError(setting)

    def installedSoftware(self, stringOut=False):
        '''Return a list of the currently installed software'''
        if stringOut:
            output = ""

            for software in self._installedSoftware():
                output += "||%s::%s::%s" % (software[0], software[1], software[2])

            return output
        else:
            try:
                return self.softwareList
            except AttributeError, attr:
                raise self.error.xmlSkipSoftwareValue(attr)

    def command(self, software):
        '''
        Return the command assoicated with the given software

        INPUT:
            software (str) -- the software to retrieve the command for
        '''
        try:
            return self.softwareCommand[software]
        except KeyError:
            raise self.error.xmlKeyError(software)

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
        except KeyError:
            raise self.error.xmlKeyError(software)

    def widgetIndex(self, software):
        '''
       Return the index of the widget for the given software

        INPUT:
            software (str) -- software to index for (using common name)
        '''
        try:
            return int(self.softwareWidgetIndex[self.commonName(software)])
        except KeyError:
            raise self.error.xmlKeyError(software)

    def fileGrep(self, software):
        '''
       Return the index of the widget for the given software

        INPUT:
            software (str) -- software to index for (using common name)
        '''
        try:
            return self.softwareFileGrep[self.commonName(software)]
        except KeyError:
            raise self.error.xmlKeyError(software)

    def frameStatusKey(self, key):
        '''
        Return the status string for a given key
        function can also return a number from a string
        '''
        try:
            if type(key) == int:
                return self.jobStatusDict[key]
            elif type(key) == str:
                for k, v in self.jobStatusDict.items():
                    if v == key:
                        return k
        except KeyError:
            raise self.error.xmlKeyError(key)

    def hostStatusKey(self, key):
        '''Return the status string for a given key'''
        try:
            return self.hostStatusDict[key]
        except KeyError:
            raise self.error.xmlKeyError(key)

    def _setBroadcastDict(self):
        '''Given the xml values setup the dictionary'''
        output = {}
        for parent in self._getElement(self.doc, 'settings'):
            for node in self._getElement(parent, 'network'):
                for type in self._getElement(node, 'server'):
                    if type.getAttribute('type') == 'broadcast':
                        output['interval'] =  int(type.getAttribute('interval'))
                        output['maxCount'] = int(type.getAttribute('maxCount'))
                    else:
                        pass
        return output

    def _setStandardStatusDict(self):
        '''Setup the standard status dictionaries'''
        strDict = {}
        intDict = {}
        index = 0
        for parent in self._getElement(self.doc, 'settings'):
            for node in self._getElement(parent, 'status'):
                for children in self._getElement(node, 'job'):
                    for child in children.childNodes:
                        if child.nodeType == 1:
                            intDict[index] = str(child.getAttribute('text'))
                            strDict[str(child.getAttribute('text'))] = index
                            index += 1
        return strDict, intDict

    def broadcastValue(self, key):
        '''Get a setting from the broadcast server section'''
        try:
            return self.broadcastDict[key]
        except KeyError:
            raise self.error.xmlKeyError(key)

    def bgColor(self, key):
        '''Get the background color of the requested status key'''
        return self.bgColorDict[key]

    def fgColor(self, key):
        '''Get the foreground color of the requested status key'''
        return self.fgColorDict[key]

    def frameStatus(self, key='Disabled'):
        '''Return the frame status string'''
        if key != 'Disabled':
            return self.frameStatusDict[key]
        else:
            return self.frameStatusDict

    def lookupStatus(self, val):
        '''Return the given text or number for the input value'''
        if type(val) == int:
            return self.intFrameStatus[val]
        elif type(val) == str:
            return self.strFrameStatus[val]

    def _setupLog(self):
        '''Read the xml logging settings and set them up for later use'''
        self.logLevels = {"ALL" : 0, "STANDARD" : 1,
                            "DEBUG" : 2, "WARNING" : 3, "ERROR" : 4, "CRITICAL" : 5,
                            "DEVEL1" : 6, "DEVEL2" : 7, "DEVEL3" :8,
                            "DEVEL4" : 9, "DEVEL5" : 10, "DEVEL6" : 11, "DEVEL" : 12,
                            "NONE" : -1
                            }
        for parent in self._getElement(self.doc, 'settings'):
            for setting in self._getElement(parent, 'status'):
                for node in self._getElement(setting, 'general'):
                    for child in node.childNodes:
                        if child.localName == 'logging':
                            # set the log level
                            level = str(child.getAttribute('level')).upper()
                            if level == "ALL":
                                self.logLevel = range(len(self.logLevels)-1)
                            elif level == "DEBUG":
                                self.logLevel = [2, 3, 4, 5]
                            elif level == "DEVEL":
                                self.logLevel = [6, 7, 8, 9, 10, 11, 12]
                            elif level == "NONE":
                                self.logLevel = [-1]
                            else:
                                self.logLevel = [self.logLevels[level]]

    def log(self, line, lvl):
        '''Write the given line to self.log ONLY if set @ the current level requested'''
        level = lvl.upper()
        if self.logLevels[level] in self.logLevel:
            print line
