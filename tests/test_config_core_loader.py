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
import uuid
from itertools import chain

try:
    from collections import OrderedDict
except ImportError:
    from ordereddict import OrderedDict

from nose.tools import raises

from utcore import TestCase
from pyfarm.files import yamlDump
from pyfarm import error
from pyfarm.ext.config.core.find import configFiles
from pyfarm.ext.config.core.loader import Loader


class ConfigTestBase(TestCase):
    def _generatedata(self):
        data = OrderedDict()
        user = self.mktempdir()
        roots = self.mktempdir()
        system = self.mktempdir()
        kwargs = {"user": user, "roots": roots, "system": system}

        for dirname in [user, roots, system]:
            filepath = os.path.join(dirname, "config.yml")
            filedata = {str(uuid.uuid4()): str(uuid.uuid4()), "test": True}
            yamlDump(filedata, filepath)
            data[filepath] = filedata

        return data, kwargs


class TestErrors(ConfigTestBase):
    def setUp(self):
        super(TestErrors, self).setUp()
        Loader._DATA.clear()


    @raises(error.PreferencesNotFoundError)
    def test_notfound(self):
        Loader("")


    @raises(AssertionError)
    def test_invalid_dict_type(self):
        Loader(data="")


    @raises(AssertionError)
    def test_invalid_typename_type(self):
        Loader(filename={})


    @raises(KeyError)
    def test_setitem_postinstance(self):
        config = Loader("", data={}, load=False)
        config["foo"] = True
        config["foo"]


    @raises(KeyError)
    def test_supdate_postinstance(self):
        config = Loader("", data={}, load=False)
        config.update({"foo": True})
        config["foo"]


class General(ConfigTestBase):
    def test_load_files(self):
        data, kwargs = self._generatedata()
        config = Loader("config.yml", **kwargs)
        self.assertEqual(set(config.files),
                         set(configFiles("config.yml", **kwargs)))

    def test_data(self):
        uuid_data = {}
        data, kwargs = self._generatedata()
        map(uuid_data.update, data.itervalues())
        config = Loader("config.yml", **kwargs)

        for key, value in config.iteritems():
            self.assertEqual(uuid_data[key], value)


class TestWhere(ConfigTestBase):
    def test_unique(self):
        dirs = [self.mktempdir() for _ in xrange(3)]
        filenames = [os.path.join(d, "config.yml") for d in dirs]

        # generate unique data
        data = OrderedDict()
        for name in filenames:
            d = {str(uuid.uuid4()): str(uuid.uuid4())}
            data[yamlDump(d, name)] = d

        config = Loader("config.yml", roots=dirs)
        for filename, data in data.iteritems():
            self.assertEqual(config.where(data.keys()[0])[0], filename)

    def test_nonunique(self):
        dirs = [self.mktempdir() for _ in xrange(3)]
        filenames = [os.path.join(d, "config.yml") for d in dirs]

        # generate non-unique data
        data = OrderedDict()
        for name in filenames:
            d = {"test": str(uuid.uuid4())}
            data[yamlDump(d, name)] = d

        config = Loader("config.yml", roots=dirs)
        self.assertEqual(config.where("test"), config.files)

    def test_unknown(self):
        dirs = [self.mktempdir() for _ in xrange(3)]
        filenames = [os.path.join(d, "config.yml") for d in dirs]

        # generate non-unique data
        data = OrderedDict()
        for name in filenames:
            d = {}
            data[yamlDump(d, name)] = d

        config = Loader("config.yml", roots=dirs)
        self.assertEqual(config.where("foo"), [])


class TestWhich(TestCase):
    def test_unique(self):
        dirs = [self.mktempdir() for _ in xrange(3)]
        filenames = [os.path.join(d, "config.yml") for d in dirs]

        # generate unique data
        data = OrderedDict()
        for name in filenames:
            d = {str(uuid.uuid4()): str(uuid.uuid4())}
            data[yamlDump(d, name)] = d

        config = Loader("config.yml", roots=dirs)
        for filename, data in data.iteritems():
            self.assertEqual(config.which(data.keys()[0]), filename)

    def test_nonunique(self):
        dirs = [self.mktempdir() for _ in xrange(3)]
        filenames = [os.path.join(d, "config.yml") for d in dirs]

        # generate non-unique data
        data = OrderedDict()
        for name in filenames:
            d = {"test": str(uuid.uuid4())}
            data[yamlDump(d, name)] = d

        config = Loader("config.yml", roots=dirs)
        self.assertEqual(config.which("test"), config.files[-1])

    def test_unknown(self):
        dirs = [self.mktempdir() for _ in xrange(3)]
        filenames = [os.path.join(d, "config.yml") for d in dirs]

        # generate non-unique data
        data = OrderedDict()
        for name in filenames:
            d = {}
            data[yamlDump(d, name)] = d

        config = Loader("config.yml", roots=dirs)
        self.assertEqual(config.which("foo"), None)


class TestGet(ConfigTestBase):
    def test_override(self):
        dirs = [self.mktempdir() for _ in xrange(3)]
        filenames = [os.path.join(d, "config.yml") for d in dirs]
        last_value = None
    
        # generate non-unique data
        data = OrderedDict()
        for name in filenames:
            d = {"test": str(uuid.uuid4())}
            data[yamlDump(d, name)] = d
            last_value = d.values()[0]
    
        config = Loader("config.yml", roots=dirs)
        self.assertEqual(config.get("test"), last_value)
    
    def test_dot_syntax(self):
        dirs = [self.mktempdir() for _ in xrange(3)]
        filenames = [os.path.join(d, "config.yml") for d in dirs]
        last_value = None
    
        # generate non-unique data
        data = OrderedDict()
        for name in filenames:
            last_value = str(uuid.uuid4())
            d = {"test": {"foo": last_value}}
            data[yamlDump(d, name)] = d
    
        config = Loader("config.yml", roots=dirs)
        self.assertEqual(config.get("test.foo"), last_value)

    def test_unknown(self):
        config = Loader("", data={}, load=False)
        self.assertEqual(config.get("", -1), -1)

    def test_known(self):
        v = str(uuid.uuid4())
        config = Loader("", data={"test": v}, load=False)
        self.assertEqual(config.get("test"), v)
