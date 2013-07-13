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
import time
import unittest
import tempfile
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

from pyfarm.utility import randstr
from pyfarm.flaskapp import app, db


def skip_on_ci(func):
    @wraps(func)
    def wrapper(*args, **kwargs):
        if "BUILDBOT_UUID" in os.environ or "TRAVIS" in os.environ:
            raise SkipTest
        return func(*args, **kwargs)
    return wrapper

class RandomPrivateIPGenerator(set):
    def __call__(self):
        while True:
            int_values = [10, randint(0, 255), randint(0, 255), randint(0, 255)]
            random_address = "".join(map(str, int_values))

            if random_address not in self:
                self.add(random_address)
                return random_address


class RandomStringGenerator(set):
    def __call__(self):
        while True:
            value = randstr()
            if value not in self:
                self.add(value)
                return value


unique_ip = RandomPrivateIPGenerator()
unique_str = RandomStringGenerator()


class TestCase(unittest.TestCase):
    # placeholder class vars
    TEMPDIR_PREFIX = ""
    BUILDBOT_UUID = os.environ.get("BUILDBOT_UUID")
    ORIGINAL_ENVIRONMENT = {}
    temp_directories = set()

    def resetEnvironment(self):
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

        except (OSError, IOError):
            pass

        else:
            if path in cls.temp_directories:
                cls.temp_directories.remove(path)

    @classmethod
    def setUpClass(cls):
        from pyfarm.ext import files as _files
        cls._files = _files
        cls.TEMPDIR_PREFIX = cls._files.DEFAULT_DIRECTORY_PREFIX
        cls.ORIGINAL_ENVIRONMENT = os.environ.copy()

    def setUp(self):
        self.tempdir = self.mktempdir()
        unique_ip.clear()
        unique_str.clear()
        os.environ.clear()
        os.environ.update(self.ORIGINAL_ENVIRONMENT)

    def tearDown(self):
        self.remove(self.tempdir)
        map(self.remove, self.temp_directories.copy())


class ModelTestCase(TestCase):
    def setUp(self):
        super(ModelTestCase, self).setUp()
        db.create_all()

    def tearDown(self):
        db.session.rollback()
        db.drop_all()
        super(ModelTestCase, self).setUp()