'''
HOMEPAGE: www.pyfarm.net
INITIAL: Aug 10 2010
PURPOSE: Runs test suite on logging config file to verify that it's
setup properly

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

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
#!/usr/bin/env python
#
# INITIAL: Aug 10 2010
# PURPOSE: Runs test suite on logging config file to verify that it's
#          setup properly
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


import re
import os
import sys
import unittest
import fnmatch
from xml.dom import minidom

CWD = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import logger

class Validate(unittest.TestCase):
    def setUp(self):
        self.log = logger.Logger('LevelTest')
        self.xml = minidom.parse(Logger.XML_CONFIG)

    def testLevelsExist(self):
        '''Make sure levels exist'''
        self.assertTrue(self.log.levels,  "Log levels do not exist")

    def testValidFunctionNames(self):
        '''Assure function names are valid'''
        containsSpaces = re.compile(r'''(.+\s.+)''')
        containsDot = re.compile(r'''(.+[.].+)''')
        for name,  data in self.log.config.items():
                self.failIf(
                                containsSpaces.search(data['function']) or "." in data['function'],
                                "'%s' is not a valid function name for Logger" % data['function']
                            )

    def testAssueEnabledIsBool(self):
        '''Assure that enabled is a boolean value'''
        for element in self.xml.getElementsByTagName("level"):
            attrib = str(element.getAttribute("enabled"))
            self.failIf(
                            attrib not in ("True", "False"),
                            "'%s' is not a valid value for logging enabled key, valid values are: True, False" % attrib
                        )

    def testAssureBoldIsBool(self):
        '''Assure bold is a boolean value'''
        for element in self.xml.getElementsByTagName("level"):
            if element.hasAttribute("bold"):
                attrib = str(element.getAttribute("bold"))
                self.failIf(
                                attrib not in ("True", "False"),
                                "'%s' is not a value for the logging bold key, valid values are: True, False" % attrib
                            )

    def testLevalValueRepeated(self):
        '''Search for repeat level values'''
        levels = []
        for element in self.xml.getElementsByTagName("level"):
            level = int(element.getAttribute("value"))
            self.failIf(
                            level in levels,
                            "Log level %i already exists,  you cannot use it twice" % level
                        )
            levels.append(level)

    def testLevelNameRepeated(self):
        '''Search for repeat level names'''
        levelNames = []
        for element in self.xml.getElementsByTagName("level"):
            name = str(element.getAttribute("name"))
            self.failIf(
                            name in levelNames,
                            "Log name %s is already exists,  you cannot use it twice" % name
                        )
            levelNames.append(name)

    def testLevelFunctionRepeated(self):
        '''Search for repeat funtion names'''
        functions = []
        for element in self.xml.getElementsByTagName("level"):
            if element.hasAttribute("function"):
                function = str(element.getAttribute("function"))
            else:
                function = str(element.getAttribute("name"))

            self.failIf(
                            function in functions,
                            "Log function %s is already exists,  you cannot use it twice" % function
                        )
            functions.append(function)

    def testMissingLogLevels(self):
        '''Verify all log levels are present in xml'''
        functions = []
        reLog = re.compile(r"""(?:log|logger|logging)[.](\w+)(?:[(])""")

        for element in self.xml.getElementsByTagName("level"):
            if element.hasAttribute("function"):
                functions.append(str(element.getAttribute("function")))
            else:
                functions.append(str(element.getAttribute("name")))

        for path, dirs, files in os.walk(PYFARM):
            for filename in fnmatch.filter(files, "*.py"):
                if not fnmatch.fnmatch(path,  "*tools*") and not fnmatch.fnmatch(filename,  "*__init__*"):
                    for line in open(os.path.join(path,  filename),  'r'):
                        search = reLog.search(line)
                        if search:
                            fName = search.group(1)
                            if fName not in functions: print line
                            self.failIf(
                                            fName not in functions,
                                            "Log function %s does not exist in the xml file" % fName
                                        )




if __name__ == "__main__":
    unittest.main()
