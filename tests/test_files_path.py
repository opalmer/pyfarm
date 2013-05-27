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
import nose
import stat
import uuid
import tempfile
from nose.tools import raises

from pyfarm.files import path

CLEAN_ENV = os.environ.copy()


def pretest():
    path.SESSION_DIRECTORY = None
    os.environ.update(CLEAN_ENV)


@nose.with_setup(setup=pretest)
def test_tempdir_session():
    sessiondir = path.tempdir(unique=False)
    assert path.tempdir(unique=False) == sessiondir == path.SESSION_DIRECTORY


@nose.with_setup(setup=pretest)
def test_tempdir_envvar():
    os.environ["PYFARM_TMP"] = tempfile.mkdtemp()
    assert path.tempdir(respect_env=True) == os.environ["PYFARM_TMP"]
    assert path.tempdir(respect_env=False) == path.SESSION_DIRECTORY


@nose.with_setup(setup=pretest)
def test_tempdir_unique():
    assert path.tempdir(
        respect_env=False,
        unique=True) != path.tempdir(respect_env=False, unique=True)


@nose.with_setup(setup=pretest)
def test_tempdir_mode():
    st_mode = os.stat(path.tempdir(unique=True)).st_mode
    assert stat.S_IMODE(st_mode) == path.DEFAULT_PERMISSIONS
    mymode = stat.S_IRWXU
    st_mode = os.stat(path.tempdir(unique=True, mode=mymode)).st_mode
    assert stat.S_IMODE(st_mode) == mymode


@nose.with_setup(setup=pretest)
def test_expandpath():
    os.environ["FOO"] = "foo"
    joined_path = os.path.join("~", "$FOO")
    expected = os.path.expanduser(os.path.expandvars(joined_path))
    assert path.expandpath(joined_path) == expected


@nose.with_setup(setup=pretest)
@raises(EnvironmentError)
def test_expandenv_raise_enverror():
    path.expandenv(str(uuid.uuid4()))


@nose.with_setup(setup=pretest)
@raises(ValueError)
def test_expandenv_raise_valuerror():
    var = str(uuid.uuid4())
    os.environ[var] = ""
    path.expandenv(var)
