#!/usr/bin/python
# PURPOSE: To read in and return the settings in an xml file

import sys
import xml.dom.minidom

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
        self.portList = self._netPort()

    def _isDomNode(self, node):
        '''Check and see if the given node is a dom node'''
        if node.nodeType == 1:
            return True
        else:
            return False

    def _groupChildren(self, nodeGroup):
        '''
        Given a node group or node path return the children of
        nodeGroup.

        INPUT:
            nodeGroup (str) -- Node group or path to search for

        EXAMPLE:
            _groupChildren('settings') -- return all of the nodes listed under settings
            _groupChildren('settings.network') -- return all of the nodes listed under
            settings.network
        '''
        # first split nodeGroup, to make sure we pickup a search
        # path if one is given
        groupSearch = nodeGroup.split('.')

        # if given a node path
        if len(groupSearch) == 2:
            for parent in self.doc.getElementsByTagName(groupSearch[0]):
                for root in parent.getElementsByTagName(groupSearch[1]):
                            for child in root.childNodes:
                                if self._isDomNode(child):
                                    yield child
                            #yield [str(child.getAttribute('type')), str(child.getAttribute('port'))]


        # if given a root node
        elif len(groupSearch) == 1:
            for child in self.doc.getElementsByTagName(nodeGroup):
                for children in child.childNodes:
                    if self._isDomNode(children):
                        yield children

        # if we did not get the requested info
        else:
            sys.exit("PyFarm :: %s :: ERROR :: Improper search parameters") % self.modName

    def _netPort(self):
        '''Return a dictionary with the server name and port'''
        portList = {}
        for child in self._groupChildren('settings.network'):
            portList[str(child.getAttribute('type'))] = str(child.getAttribute('port'))

        return portList

    def netPort(self, service):
        '''
        Return the port for the given service

        INPUT:
            service (str) -- service to return a listing for
        '''
        return int(self.portList[service])

settings = ParseXmlSettings('settings.xml')
