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
import time
import unittest
import tempfile
import traceback
from random import randint
from functools import wraps

try:
    import json
except ImportError:
    import simplejson as json

from nose.plugins.skip import SkipTest

# if there's some configuration information in the
# environment then use that before creating our own
if "PYFARM_DBCONFIG_TESTING_DATA" in os.environ:
    DB_CONFIG_DATA = json.loads(os.environ["PYFARM_DBCONFIG_TESTING_DATA"])
else:
    DB_CONFIG_DATA = {"engine": "sqlite", "database": ":memory:"}

# insert the configuration data
from pyfarm.ext.config.database import DBConfig
DBConfig.insertConfig("unittest_%s" % time.time(), DB_CONFIG_DATA)

from pyfarm.flaskapp import app, db


def skip_on_ci(func):
    @wraps(func)
    def wrapper(*args, **kwargs):
        if "BUILDBOT_UUID" in os.environ or "TRAVIS" in os.environ:
            raise SkipTest
        return func(*args, **kwargs)
    return wrapper


class RandomPrivateIPGenerator(object):
    generated = set()
    _random = lambda self: randint(0, 255)

    def __call__(self):
        while True:
            randip = [10, self._random(), self._random(), self._random()]

            if randip not in self.generated:
                self.generated.add(randip)
                return ".".join(map(str, randip))

random_private_ip = RandomPrivateIPGenerator()


class TestCase(unittest.TestCase):
    # placeholder class vars
    TEMPDIR_PREFIX = ""
    BUILDBOT_UUID = os.environ.get("BUILDBOT_UUID")
    ORIGINAL_ENVIRONMENT = {}
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
    def clearGeneratedIPs(cls):
        RandomPrivateIPGenerator.generated.clear()

    @classmethod
    def setupFiles(cls):
        try:
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
    def setUpClass(cls):
        cls.setupFiles()
        cls.clearGeneratedIPs()

    @classmethod
    def setUpCase(self):
        """called after setUpClass is complete"""

    def setUp(self):
        self._resetEnvironment()
        self.tempdir = self.mktempdir()
        self.app = app.test_client()
        db.create_all()

    def tearDown(self):
        self._resetEnvironment()
        self._cleanupDirectories()
        db.session.rollback()
        db.session.clear()
        db.drop_all()

class ModelTestCase(TestCase):
    @classmethod
    def setUpClass(cls):
        cls.setupFiles()
        cls.clearGeneratedIPs()

    def setUp(self):
        db.create_all()

    def tearDown(self):
        db.drop_all()