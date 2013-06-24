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
import shutil
import fnmatch
import unittest
import tempfile
import traceback

from pyfarm.ext import files
from pyfarm.ext.config.database import DBConfig


class TestCase(unittest.TestCase):
    TMPDIR_PREFIX = files.DEFAULT_DIRECTORY_PREFIX
    ORIGINAL_ENVIRONMENT = os.environ.copy()

    @classmethod
    def getTempDir(cls):
        return tempfile.mkdtemp(prefix=cls.TMPDIR_PREFIX)

    @classmethod
    def resetEnvironment(cls):
        os.environ.clear()
        os.environ.update(cls.ORIGINAL_ENVIRONMENT)

    @classmethod
    def remove(cls, path):
        delete = None
        assert isinstance(path, basestring), "expected a string for `path`"

        # determine path type
        if os.path.isfile(path):
            delete = os.remove
        elif os.path.isdir(path):
            delete = shutil.rmtree

        # delete the path
        try:
            if delete is not None:
                delete(path)

        except (IOError, OSError):
            pass

    @classmethod
    def setUpClass(cls):
        try:
            DBConfig.createConfig("unittest",
                                  {"engine": "sqlite", "database": ":memory:"})

            if "travis" in cls.ORIGINAL_ENVIRONMENT:
                cls.ONTRAVIS = True
            else:
                cls.ONTRAVIS = False

        # if an exception is raised here the traceback is typically
        # not very helpful we preprocess it before nose/unittests
        # can pick it up
        except Exception, e:
            traceback.print_exc(e, sys.stderr)
            print >> sys.stderr, "setUpClass ERROR: %s" % e

    # @classmethod
    # def tearDownClass(cls):
    #     try:
    #         pass
    #     except Exception, e:
    #         traceback.print_exc(e, sys.stderr)
    #         print >> sys.stderr, "tearDownClass ERROR: %s" % e

    def setUp(self):
        self.resetEnvironment()
        self.tmpdir = self.getTempDir()
        self.environ = os.environ.copy()  # local copy we can test with

    def tearDown(self):
        self.resetEnvironment()
        self.remove(self.tmpdir)

        # construct a list of paths to delete, filter, then remove them
        alldirs = os.listdir(files.DEFAULT_DIRECTORY_PREFIX)
        dirlist = [self.tmpdir]
        dirlist.extend(fnmatch.filter(alldirs, "%s*" % self.TMPDIR_PREFIX))
        map(self.remove, dirlist)