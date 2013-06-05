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
from nose.tools import with_setup, raises, eq_
from pyfarmnose import mktmp, posttest_cleanup_files, pretest_cleanup_env
from pyfarm.exthook import ExtensionImporter

MODULE_DIR = None
META_PATH = sys.meta_path[:]


def prerun():
    global MODULE_DIR
    pretest_cleanup_env()
    MODULE_DIR = mktmp()
    sys.path.insert(0, MODULE_DIR)


def postrun():
    global MODULE_DIR
    sys.path.remove(MODULE_DIR)
    MODULE_DIR = None
    posttest_cleanup_files()
    sys.meta_path[:] = META_PATH


envsetup = with_setup(setup=prerun, teardown=postrun)


@envsetup
@raises(AssertionError)
def test_choices_error():
    ExtensionImporter("", "")


@envsetup
@raises(AssertionError)
def test_wrapper_error():
    ExtensionImporter(["foo"], None)


@envsetup
@raises(AssertionError)
def test_choices_count_error():
    ExtensionImporter([], None)


@envsetup
@raises(ImportError)
def test_import_error():
    loader = ExtensionImporter(ExtensionImporter.DEFAULT_CHOICES, "")
    loader.install()
    from pyfarm.ext import foo


@envsetup
def test_install():
    loader = ExtensionImporter(["foo"], "")
    loader.install()
    eq_(loader in sys.meta_path, True)


@envsetup
def test_hook():
    module_dirname = os.path.join(MODULE_DIR, "pyfarm_foobar")
    module_filepath = os.path.join(module_dirname, "foo.py")
    sys.path.insert(0, MODULE_DIR)

    os.makedirs(module_dirname)
    with open(os.path.join(module_dirname, "__init__.py"), "w") as init:
        print >> init, ""

    with open(module_filepath, "w") as foobar:
        print >> foobar, "test = lambda: True"
        print foobar.name

    loader = ExtensionImporter(ExtensionImporter.DEFAULT_CHOICES, "")
    loader.install()

    from pyfarm.ext.foobar import foo
    from pyfarm.ext.foobar.foo import test
    eq_(test(), True)
    eq_(inspect.getfile(test), foobar.name)