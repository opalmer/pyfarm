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

import os
import unittest
from pyfarm.config.database import DBConfig


class TestCase(unittest.TestCase):
    DBConfig.createConfig("unittest",
                          {"engine": "sqlite", "database": ":memory:"})
    ORIGINAL_ENVIRONMENT = os.environ.copy()

    @classmethod
    def setUpClass(cls):
        if "travis" in cls.ORIGINAL_ENVIRONMENT:
            cls.ONTRAVIS = True
        else:
            cls.ONTRAVIS = False

    def getTempdir(self):
        return

    def setUp(self):
        os.environ.clear()
        os.environ.update(self.ORIGINAL_ENVIRONMENT)
        # TODO: create directory for test

    def tearDown(self):
        os.environ.clear()
        os.environ.update(self.ORIGINAL_ENVIRONMENT)
        # TODO: remove directory for test