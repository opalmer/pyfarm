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
import nose

from core.prepost import mktmp, pretest_cleanup_env, posttest_cleanup_files
from pyfarm.files.stream import TempFile, ymlload, ymldump


setup = nose.with_setup(
    setup=pretest_cleanup_env,
    teardown=posttest_cleanup_files
)


@setup
def test_tempfile_delete():
    with TempFile(delete=True) as s:
        assert os.path.isfile(s.name)
    assert not os.path.isfile(s.name)


@setup
def test_tempfile_nodelete():
    with TempFile(delete=False) as s:
        assert os.path.isfile(s.name)
    assert os.path.isfile(s.name)


@setup
def test_tempfile_dirname():
    d = mktmp()
    with TempFile(root=d, delete=True) as s:
        assert os.path.dirname(s.name) == d

@setup
def test_tempfile_basename():
    d = mktmp()
    with TempFile(prefix="foo", suffix=".txt", root=d, delete=True) as s:
        base = os.path.basename(s.name)
        assert base.startswith("foo")
        assert base.endswith(".txt")
        