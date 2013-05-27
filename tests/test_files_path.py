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
import shutil
import tempfile
from fnmatch import filter as fnfilter
from nose.tools import raises

from pyfarm.files import path

CLEAN_ENV = os.environ.copy()


raise Exception("needs test: which")


def pretest():
    path.SESSION_DIRECTORY = None

    for varname, value in os.environ.copy().iteritems():
        if varname not in CLEAN_ENV:
            del os.environ[varname]
        elif os.environ[varname] != CLEAN_ENV[varname]:
            os.environ[varname] = CLEAN_ENV[varname]


def posttest():
    # remove any directories we created
    prefix = path.DEFAULT_DIRECTORY_PREFIX
    root = os.path.dirname(
        tempfile.mkdtemp(prefix=prefix)
    )
    for dirname in fnfilter(os.listdir(root), "%s*" % prefix):
        tmpdirectory = os.path.join(root, dirname)
        shutil.rmtree(tmpdirectory)


nosesetup = nose.with_setup(setup=pretest, teardown=posttest)
mktmp = lambda: tempfile.mkdtemp(prefix=path.DEFAULT_DIRECTORY_PREFIX)


@nosesetup
def test_tempdir_session():
    sessiondir = path.tempdir(unique=False)
    assert path.tempdir(unique=False) == sessiondir == path.SESSION_DIRECTORY


@nosesetup
def test_tempdir_envvar():
    os.environ["PYFARM_TMP"] = mktmp()
    assert path.tempdir(respect_env=True) == os.environ["PYFARM_TMP"]
    assert path.tempdir(respect_env=False) == path.SESSION_DIRECTORY


@nosesetup
def test_tempdir_unique():
    assert path.tempdir(
        respect_env=False,
        unique=True) != path.tempdir(respect_env=False, unique=True)


@nosesetup
def test_tempdir_mode():
    st_mode = os.stat(path.tempdir(unique=True)).st_mode
    assert stat.S_IMODE(st_mode) == path.DEFAULT_PERMISSIONS
    mymode = stat.S_IRWXU
    st_mode = os.stat(path.tempdir(unique=True, mode=mymode)).st_mode
    assert stat.S_IMODE(st_mode) == mymode


@nosesetup
def test_expandpath():
    os.environ["FOO"] = "foo"
    joined_path = os.path.join("~", "$FOO")
    expected = os.path.expanduser(os.path.expandvars(joined_path))
    assert path.expandpath(joined_path) == expected


@nosesetup
@raises(EnvironmentError)
def test_expandenv_raise_enverror():
    path.expandenv(str(uuid.uuid4()))


@nosesetup
@raises(ValueError)
def test_expandenv_raise_valuerror():
    var = str(uuid.uuid4())
    os.environ[var] = ""
    path.expandenv(var)


@nosesetup
def test_expandenv_path_validation():
    envvars = {
        "FOO1": mktmp(), "FOO2": mktmp(),
        "FOO3": "<unknown_foo>",
        "FOOBARA": os.pathsep.join(["$FOO1", "$FOO2", "$FOO3"])
    }
    os.environ.update(envvars)
    assert path.expandenv("FOOBARA") == [os.environ["FOO1"], os.environ["FOO2"]]


@nosesetup
def test_expandenv_path_novalidation():
    envvars = {
        "FOO4": mktmp(), "FOO5": mktmp(),
        "FOO6": "<unknown_foo>",
        "FOOBARB": os.pathsep.join(["$FOO5", "$FOO4", "$FOO6"])
    }
    os.environ.update(envvars)
    expanded = path.expandenv("FOOBARB", validate=False)
    assert expanded == [
        os.environ["FOO5"], os.environ["FOO4"], os.environ["FOO6"]
    ]