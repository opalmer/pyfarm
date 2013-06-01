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

from nose.tools import raises
from nose.plugins.skip import SkipTest

from pyfarmnose.prepost import mktmp, envsetup
from pyfarm.files.stream import TempFile, loadYaml, dumpYaml
from pyfarm.files import path


@envsetup
def test_tempfile_delete():
    # travis seems to have issues with deleting
    # files on time, this should pass locally however
    if "TRAVIS" in os.environ:
        raise SkipTest

    with TempFile(delete=True) as s:
        assert os.path.isfile(s.name)

    max_time = 15
    start = time.time()

    while time.time()-start <= max_time:
        if not os.path.isfile(s.name):
            break

        time.sleep(.1)

    assert not os.path.isfile(s.name)


@envsetup
def test_tempfile_nodelete():
    with TempFile(delete=False) as s:
        assert os.path.isfile(s.name)
    assert os.path.isfile(s.name)


@envsetup
def test_tempfile_dirname():
    d = mktmp()
    with TempFile(root=d, delete=True) as s:
        assert os.path.dirname(s.name) == d

@envsetup
def test_tempfile_basename():
    d = mktmp()
    with TempFile(prefix="foo", suffix=".txt", root=d, delete=True) as s:
        base = os.path.basename(s.name)
        assert base.startswith("foo")
        assert base.endswith(".txt")


@envsetup
@raises(TypeError)
def test_dumpyaml_error():
    dumpYaml("", lambda: None)


@envsetup
def test_dumpyaml_tmppath():
    dump_path = dumpYaml("")
    assert dump_path.endswith(".yml")
    assert os.path.dirname(dump_path) == path.SESSION_DIRECTORY


@envsetup
def test_dumpyaml_path():
    d = mktmp()
    expected_dump_path = os.path.join(d, "foo", "foo.yml")
    dump_path = dumpYaml("", path=expected_dump_path)
    assert os.path.isdir(os.path.dirname(expected_dump_path))
    assert dump_path == expected_dump_path

@envsetup
@raises(TypeError)
def test_loadyaml_error():
    loadYaml(lambda: None)

@envsetup
def test_loadyaml_path():
    data = os.environ.data.copy()
    dumped_path = dumpYaml(data)
    assert loadYaml(dumped_path) == data

@envsetup
def test_loadyaml_stream():
    data = os.environ.data.copy()
    dumped_path = dumpYaml(data)
    s = open(dumped_path, "r")
    assert loadYaml(s) == data
    assert s.closed