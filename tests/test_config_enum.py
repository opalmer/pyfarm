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

import sys
import uuid
import warnings

from utcore import TestCase
from pyfarm.warning import ConfigurationWarning
from pyfarm.ext.config.core.loader import Loader
from pyfarm.ext.config import enum

SYS_PLATFORM = sys.platform


class Enum(TestCase):
    SYS_PLATFORM = sys.platform

    def setUp(self):
        super(Enum, self).setUp()
        sys.platform = self.SYS_PLATFORM

    def tearDown(self):
        super(Enum, self).tearDown()
        sys.platform = self.SYS_PLATFORM

    def test_enum_subclasses(self):
        loader = Loader("enums.yml")
        enum_vars = vars(enum)

        for enum_name in loader:
            if enum_name in enum_vars:
                self.assertEqual(
                    isinstance(enum_vars[enum_name], enum.EnumBuilder), True)

    def test_enum_operatingsystem(self):
        osenum = enum.OperatingSystem()
        platforms = {
            "linux": osenum.LINUX, "darwin": osenum.MAC,
            "win": osenum.WINDOWS, "foobar": osenum.OTHER
        }
        warnings.simplefilter("ignore", ConfigurationWarning)

        for sysplatform, value in platforms.iteritems():
            sys.platform = sysplatform
            self.assertEqual(osenum.get(), value)

        warnings.resetwarnings()

    def test_enum_attr_keys(self):
        enumdata = Loader("enums.yml")
        for name, data in enumdata.iteritems():
            if name in vars(enum):
                enum_class = vars(enum)[name]
                instance = enum_class()
                for key in data.iterkeys():
                    self.assertEqual(
                        hasattr(instance, key), True,
                        "enums %s is missing the %s attribute" % (name, key)
                    )

    def test_enum_attr_values(self):
        enumdata = Loader("enums.yml")
        for name, data in enumdata.iteritems():
            if name in vars(enum):
                enum_class = vars(enum)[name]
                instance = enum_class()
                for key, value in data.iteritems():
                    self.assertEqual(getattr(instance, key), value)

    def test_methods(self):
        enumdata = Loader("enums.yml")
        for name, data in enumdata.iteritems():
            if name in vars(enum):
                enum_class = vars(enum)[name]
                instance = enum_class()
                self.assertEqual(sorted(instance.keys()),
                                 sorted(data.keys()))
                self.assertEqual(sorted(instance.values()),
                                 sorted(data.values()))

                new_key = str(uuid.uuid4())
                new_value = str(uuid.uuid4())
                instance._mapped[new_key] = new_value
                self.assertEqual(instance.get(new_key), new_value)