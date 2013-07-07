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
import unittest
import tempfile
import traceback
from functools import wraps

from nose.plugins.skip import SkipTest


class skip_if_true(object):
    """Decorator which is used to skip a test given a condition"""
    def __init__(self, condition):
        self.condition = condition

    def __call__(self, func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            if self.condition:
                raise SkipTest

            return func(*args, **kwargs)

        return wrapper


@skip_if_true("BUILDBOT_UUID" in os.environ)
@skip_if_true("TRAVIS" in os.environ)
def skip_on_ci(func):
    @wraps(func)
    def wrapper(*args, **kwargs):
        return func(*args, **kwargs)
    return wrapper


class TestCase(unittest.TestCase):
    temp_directories = set()

    def _cleanupDirectories(self):
        map(self.remove, self.temp_directories.copy())
        self.remove(self.tempdir)

    def _resetEnvironment(self):
        os.environ.clear()
        os.environ.update(self.ORIGINAL_ENVIRONMENT)

    @classmethod
    def mktempdir(cls):
        tempdir = tempfile.mkdtemp(prefix=cls.TEMPDIR_PREFIX)
        cls.temp_directories.add(tempdir)
        return tempdir

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

        else:
            if path in cls.temp_directories:
                cls.temp_directories.remove(path)

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
        else:
            cls.setUpCase()

    @classmethod
    def tearDownClass(cls):
        cls.tearDownCase()

    @classmethod
    def setUpCase(self):
        """called after setUpClass is complete"""

    @classmethod
    def tearDownCase(cls):
        """called after tearDownClass is complete"""

    def setUp(self):
        self._resetEnvironment()
        self.tempdir = self.mktempdir()

    def tearDown(self):
        self._resetEnvironment()
        self._cleanupDirectories()
