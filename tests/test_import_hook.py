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

from __future__ import with_statement

import os
import sys
import inspect

META_PATH = sys.meta_path[:]

from nose.tools import raises
from utcore import TestCase
from pyfarm.exthook import ExtensionImporter


class ImportHook(TestCase):
    def setUp(self):
        super(ImportHook, self).setUp()
        init_file = os.path.join(self.tempdir, "__init__.py")
        with open(init_file, "w") as init_file_stream:
            print >> init_file_stream, ""

        sys.path.insert(0, self.tempdir)

    def tearDown(self):
        super(ImportHook, self).tearDown()
        sys.path.remove(self.tempdir)
        sys.meta_path[:] = META_PATH
        
    @raises(AssertionError)
    def test_choices_error(self):
        ExtensionImporter("", "")

    @raises(AssertionError)
    def test_wrapper_error(self):
        ExtensionImporter(["foo"], None)


    @raises(AssertionError)
    def test_choices_count_error(self):
        ExtensionImporter([], None)

    @raises(ImportError)
    def test_import_error(self):
        loader = ExtensionImporter(ExtensionImporter.DEFAULT_CHOICES, "")
        loader.install()
        from pyfarm.ext import foo

    def test_install(self):
        loader = ExtensionImporter(["foo"], "")
        loader.install()
        self.assertEqual(loader in sys.meta_path, True)

    def test_hook(self):
        module_dirname = os.path.join(self.tempdir, "pyfarm_foobar")
        module_filepath = os.path.join(module_dirname, "foo.py")
        sys.path.insert(0, self.tempdir)

        os.makedirs(module_dirname)
        with open(os.path.join(module_dirname, "__init__.py"), "w") as init:
            print >> init, ""

        with open(module_filepath, "w") as foobar:
            print >> foobar, "test = lambda: True"

        loader = ExtensionImporter(ExtensionImporter.DEFAULT_CHOICES, "")
        loader.install()

        from pyfarm.ext.foobar import foo
        from pyfarm.ext.foobar.foo import test
        self.assertEqual(test(), True)
        self.assertEqual(inspect.getfile(test), foobar.name)