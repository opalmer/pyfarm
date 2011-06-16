#!/usr/bin/env python
#
# INITIAL: Oct 07 2010
# PURPOSE: Ensure all modules will import properly and are included in
#          the current installation
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
import sys
import site
import fnmatch
import unittest
import modulefinder
import ConfigParser

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(CWD, "..", ".."))
site.addsitedir(root)

class ModuleTests(unittest.TestCase):
    '''Various tests and checks for module imports and their versions'''
    def setUp(self):
        self.pyExtensions = ("py", "pyw")
        self.pyExclusions = ("*__init__*", "*tools*", "*test*")
        self.cfg = ConfigParser.RawConfigParser()
        self.cfg.read(os.path.join(PYFARM, 'cfg', 'versions.ini'))

    def _isExcluded(self, filename):
        '''Ensure the given filename is not part the the excluded list'''
        for exclusion in self.pyExclusions:
            if fnmatch.fnmatch(filename, exclusion):
                return True
        return False

    def _validPyFile(self, filename):
        '''Return true if the file is a valid python file'''
        for ext in self.pyExtensions:
            if filename.endswith(ext):
                return True
            else:
                return False

    def _pyFiles(self, root):
        '''Find all valid python files given a root directory'''
        for root, dirs, files in os.walk(root):
            for filename in files:
                filename = os.path.join(root, filename)
                if self._validPyFile(filename) and not self._isExcluded(filename):
                    yield filename

    def _getVersionConfig(self, key, level, asString=False):
        '''
        Retrieve a key, version, and convert that information to string format
        if requested.
        '''
        version = self.cfg.get(level, key)
        if not str(version).upper() in ("NONE", "FALSE", "0"):
            if not asString:
                return tuple([ int(i) for i in version.split('.') ])
            else:
                return version
        else:
            return False

    def _pyVersion(self, asString=False):
        '''Return the installed python version'''
        version = sys.version_info
        if not asString:
            return version[:2]
        else:
            return '.'.join([str(i) for i in version[:2]])

    def _pyqtVersion(self, asString=False):
        '''Return the installed pyqt version'''
        from PyQt4 import QtCore
        version = QtCore.PYQT_VERSION_STR
        if not asString:
            return tuple([int(i) for i in version.split('.')][:2])
        else:
            return version

    def _badModules(self, moduleSearch):
        '''
        Return a list of modules that had trouble importing then remove then
        from the dictionary
        '''
        output = []
        for module, namespace in moduleSearch.badmodules.items():
            if "__main__" in namespace.keys():
                output.append(module)
                del moduleSearch.badmodules[module]

        return output

    def testModuleImports(self):
        '''Ensure all modules that are in use can be imported'''
        badDict = {}
        moduleSearch = modulefinder.ModuleFinder()
        for filename in self._pyFiles(CWD):
            moduleSearch.run_script(filename)
            badModules = self._badModules(moduleSearch)
            if badModules: badDict[filename] = badModules

        self.failIf(
                    len(badDict.keys()),
                    "Failed to import one or more modules: %s" % badDict
                   )


    def testPythonVersion(self):
        """Ensure the Python version meets PyFarm's requirements"""
        module = "python"
        version = self._pyVersion()
        versionStr = self._pyVersion(asString=True)
        iniMinVersionStr = self._getVersionConfig(module, 'min', asString=True)
        iniMinVersion = self._getVersionConfig(module, 'min')
        iniMaxVersionStr = self._getVersionConfig(module, 'max', asString=True)
        iniMaxVersion = self._getVersionConfig(module, 'max')

        self.failIf(
                    version < iniMinVersion,
                    "Python %s Does Not Meet Minimum Version: %s" %
                    (versionStr, iniMinVersionStr)
                   )

        self.failIf(
                    version > iniMaxVersion,
                    "Python %s Exceeds Maximum Version: %s" %
                    (versionStr, iniMaxVersionStr)
                   )

    def testPyQtVersion(self):
        """Ensure the PyQt version meets PyFarm's requirements"""
        module = "pyqt"
        version = self._pyqtVersion()
        versionStr = self._pyqtVersion(asString=True)
        iniMinVersionStr = self._getVersionConfig(module, 'min', asString=True)
        iniMinVersion = self._getVersionConfig(module, 'min')
        iniMaxVersionStr = self._getVersionConfig(module, 'max', asString=True)
        iniMaxVersion = self._getVersionConfig(module, 'max')

        self.failIf(
                    version < iniMinVersion,
                    "PyQt %s Does Not Meet Minimum Version: %s" %
                    (versionStr, iniMinVersionStr)
                   )

        self.failIf(
                    version > iniMaxVersion,
                    "PyQt %s Exceeds Maximum Version: %s" %
                    (versionStr, iniMaxVersionStr)
                   )


if __name__ == "__main__":
    unittest.main()
