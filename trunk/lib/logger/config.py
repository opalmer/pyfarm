# No shebang line, this module is meant to be imported
#
# INITIAL: June 19 2011
# PURPOSE: To find, parse, and return a logger configuration
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
import re
import types
import xml.etree.ElementTree

# root directory setup
cwd = os.path.dirname(os.path.abspath(__file__))
cfg = os.path.join(cwd, "cfg")

# config files
xmlLevels = os.path.join(cfg, "levels.xml")

def getXml(xmlPath, root):
    '''Parse an xml file and return a list of elements matching root'''
    tree = xml.etree.ElementTree.parse(xmlPath)

    # return all elements with tags that match root
    return [entry for entry in tree.findall(root) if entry.tag == root]


class Levels(object):
    RequiredKeys = ["name"]
    PopulateKeys = {"function" : "name"}

    def __init__(self, config):
        self.config = getXml(config, "level")
        self.data = self.__data()

    def __checkKeys(self, data):
        '''
        Check and ensure that all keys in Levels.RequiredKeys exist and are
        present in data
        '''
        for key in Levels.RequiredKeys:
            if not data.has_key(key):
                raise KeyError("missing required key: %s" % key)

    def __populateKeys(self, data):
        '''
        Given the key/value pairs in Levels.PopulateKeys ensure that each key
        exists in data and if not populate it from value.  If value itself is a
        string we use that to perform a lookup in data, otherwise we simply set
        the value of data[key] to be equal to the value from Levels.PopulateKeys
        '''
        for key, value in Levels.PopulateKeys.items():
            if not data.has_key(key):
                if type(value) == types.StringType:
                    data[key] = data[value]
                else:
                    data[key] = value

    def __validateFunction(self, data):
        '''
        Ensure that all function names in data are valid (no spaces, periods,
        or other characters that are not allowed in function names
        '''
        function = data.get("function")

        # ensure the function name is a string
        if not type(function) == types.StringType:
            raise TypeError("Invalid type for function, was expecting a string")

        # ensure that we have only ascii characters/numbers in the
        # function name
        if re.search("\W", function):
            print "Removing non-alphanumeric values from %s" % function
            data["function"] = re.sub("\W", "_", data["function"])

    def __data(self):
        '''Read and return information from the xml file'''
        levels = []

        for attrib in [ entry.attrib for entry in self.config ]:
            self.__checkKeys(attrib)
            self.__populateKeys(attrib)
            self.__validateFunction(attrib)
            levels.append(attrib)

        return levels


class ReadConfig(object):
    '''Read and return a configuration'''
    def __init__(self):
        self.levels = Levels(xmlLevels).data

# for testing purposes
if __name__ == '__main__':
    config = ReadConfig()
    print config.levels