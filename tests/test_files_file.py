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
import time

from nose.tools import raises, eq_
from nose.plugins.skip import SkipTest

from tests.pyfarmnose import mktmp, envsetup
from pyfarm.ext.files.file import TempFile, yamlLoad, yamlDump
from pyfarm.ext.files import path


@envsetup
def test_tempfile_delete():
    # travis seems to have issues with deleting
    # files on time, this should pass locally however
    if "TRAVIS" in os.environ:
        raise SkipTest

    with TempFile(delete=True) as s:
        eq_(os.path.isfile(s.name), True)

    max_time = 15
    start = time.time()

    while time.time()-start <= max_time:
        if not os.path.isfile(s.name):
            break

        time.sleep(.1)

    eq_(os.path.isfile(s.name), False)


@envsetup
def test_tempfile_nodelete():
    with TempFile(delete=False) as s:
        eq_(os.path.isfile(s.name), True)

    eq_(os.path.isfile(s.name), True)


@envsetup
def test_tempfile_dirname():
    d = mktmp()
    with TempFile(root=d, delete=True) as s:
        eq_(os.path.dirname(s.name), d)


@envsetup
def test_tempfile_basename():
    d = mktmp()
    with TempFile(prefix="foo", suffix=".txt", root=d, delete=True) as s:
        base = os.path.basename(s.name)
        eq_(base.startswith("foo"), True, "%s does not start with foo" % base)
        eq_(base.endswith(".txt"), True, "%s does not end with .txt" % base)


@envsetup
@raises(TypeError)
def test_dumpyaml_error():
    yamlDump("", lambda: None)


@envsetup
def test_dumpyaml_tmppath():
    dump_path = yamlDump("")
    eq_(dump_path.endswith(".yml"), True, "%s does end with .yml" % dump_path)
    eq_(os.path.dirname(dump_path), path.SESSION_DIRECTORY)


@envsetup
def test_dumpyaml_path():
    d = mktmp()
    expected_dump_path = os.path.join(d, "foo", "foo.yml")
    dump_path = yamlDump("", path=expected_dump_path)
    eq_(os.path.isdir(os.path.dirname(expected_dump_path)), True)
    eq_(dump_path, expected_dump_path)


@envsetup
@raises(TypeError)
def test_loadyaml_error():
    yamlLoad(lambda: None)


@envsetup
def test_loadyaml_path():
    data = os.environ.data.copy()
    dumped_path = yamlDump(data)
    eq_(yamlLoad(dumped_path), data)


@envsetup
def test_loadyaml_stream():
    data = os.environ.data.copy()
    dumped_path = yamlDump(data)
    s = open(dumped_path, "r")
    eq_(yamlLoad(s), data)
    eq_(s.closed, True, "%s not closed" % s.name)