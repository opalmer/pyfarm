import re
import os
import sys
import os.path
import unittest
import fnmatch
from xml.dom import minidom

rootDir = os.path.abspath(__file__)
for i in range(3): rootDir = os.path.dirname(rootDir)
if rootDir not in sys.path: sys.path.append(rootDir)

from lib.Logger import Logger

class Levels(unittest.TestCase):
    def setUp(self):
        self.log = Logger('LevelTest')
        self.xml = minidom.parse(self.log.xml)

    def testLevelsExist(self):
        '''Make sure levels exist'''
        self.assertTrue(self.log.levels,  "Log levels do not exist")

    def testValidFunctionNames(self):
        '''Make sure all function names are valid'''
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
        '''Test for repeat level values'''
        levels = []
        for element in self.xml.getElementsByTagName("level"):
            level = int(element.getAttribute("value"))
            self.failIf(
                            level in levels,
                            "Log level %i already exists,  you cannot use it twice" % level
                        )
            levels.append(level)

    def testLevelNameRepeated(self):
        '''Fail on repeated names'''
        levelNames = []
        for element in self.xml.getElementsByTagName("level"):
            name = str(element.getAttribute("name"))
            self.failIf(
                            name in levelNames,
                            "Log name %s is already exists,  you cannot use it twice" % name
                        )
            levelNames.append(name)

    def testLevelFunctionRepeated(self):
        '''Fail on repeated functions'''
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
        '''Search for missing log information'''
        functions = []
        reLog = re.compile(r"""(?:log|logger|logging)[.](\w+)(?:[(])""")

        for element in self.xml.getElementsByTagName("level"):
            if element.hasAttribute("function"):
                functions.append(str(element.getAttribute("function")))
            else:
                functions.append(str(element.getAttribute("name")))

        for path, dirs, files in os.walk(rootDir):
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
