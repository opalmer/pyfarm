# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

"""
Module containing the base test class and functions
used by the unittests.
"""

import os
import sys
import fnmatch
import unittest
import tempfile
import traceback


class TestCase(unittest.TestCase):
    @classmethod
    def mktempdir(cls):
        return tempfile.mkdtemp(prefix=cls.TEMPDIR_PREFIX)

    @classmethod
    def remove(cls, path):
        assert isinstance(path, basestring), "expected a string for `path`"

        if os.path.isfile(path):
            delete = cls._files.remove
        elif os.path.isdir(path):
            delete = cls._files.rmtree
        else:
            delete = lambda path: None

        # delete the path
        try:
            delete(path)

        except EnvironmentError:
            pass

    @classmethod
    def setUpClass(cls):
        try:
            from pyfarm.ext.config.database import DBConfig
            DBConfig.createConfig("unittest",
                                  {"engine": "sqlite", "database": ":memory:"})
            from pyfarm.ext import files as _files

            # global class variables
            cls._files = _files
            cls.TEMPDIR_PREFIX = cls._files.DEFAULT_DIRECTORY_PREFIX
            cls.BUILDBOT_UUID = os.environ.get("BUILDBOT_UUID")
            cls.ORIGINAL_ENVIRONMENT = os.environ.copy()

        # if an exception is raised here the traceback is typically
        # not very helpful we preprocess it before nose/unittests
        # can pick it up
        except Exception, e:
            traceback.print_exc(e, sys.stderr)
            print >> sys.stderr, "setUpClass ERROR: %s" % e
            raise

    def setUp(self):
        # environment reset
        os.environ.clear()
        os.environ.update(self.ORIGINAL_ENVIRONMENT)
        self.tempdir = self.mktempdir()

    def tearDown(self):
        os.environ.clear()
        os.environ.update(self.ORIGINAL_ENVIRONMENT)
        self.remove(self.tempdir)
        alldirs = os.listdir(self._files.DEFAULT_DIRECTORY_PREFIX)
        dirlist = [self.tempdir]
        dirlist.extend(fnmatch.filter(alldirs, "%s*" % self.TEMPDIR_PREFIX))
        map(self.remove, dirlist)