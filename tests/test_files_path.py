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
import stat
import uuid
import tempfile
from nose.tools import raises

from pyfarmnose.prepost import mktmp, envsetup
from pyfarm.files import path


@envsetup
def test_tempdir_session():
    sessiondir = path.tempdir(unique=False)
    assert path.tempdir(unique=False) == sessiondir == path.SESSION_DIRECTORY


@envsetup
def test_tempdir_envvar():
    os.environ["PYFARM_TMP"] = mktmp()
    assert path.tempdir(respect_env=True) == os.environ["PYFARM_TMP"]
    assert path.tempdir(respect_env=False) == path.SESSION_DIRECTORY


@envsetup
def test_tempdir_unique():
    assert path.tempdir(
        respect_env=False,
        unique=True) != path.tempdir(respect_env=False, unique=True)


@envsetup
def test_tempdir_mode():
    st_mode = os.stat(path.tempdir(unique=True)).st_mode
    assert stat.S_IMODE(st_mode) == path.DEFAULT_PERMISSIONS
    mymode = stat.S_IRWXU
    st_mode = os.stat(path.tempdir(unique=True, mode=mymode)).st_mode
    assert stat.S_IMODE(st_mode) == mymode


@envsetup
def test_expandpath():
    os.environ["FOO"] = "foo"
    joined_path = os.path.join("~", "$FOO")
    expected = os.path.expanduser(os.path.expandvars(joined_path))
    assert path.expandpath(joined_path) == expected


@envsetup
@raises(EnvironmentError)
def test_expandenv_raise_enverror():
    path.expandenv(str(uuid.uuid4()))


@envsetup
@raises(ValueError)
def test_expandenv_raise_valuerror():
    var = str(uuid.uuid4())
    os.environ[var] = ""
    path.expandenv(var)


@envsetup
def test_expandenv_path_validation():
    envvars = {
        "FOO1": mktmp(), "FOO2": mktmp(),
        "FOO3": "<unknown_foo>",
        "FOOBARA": os.pathsep.join(["$FOO1", "$FOO2", "$FOO3"])
    }
    os.environ.update(envvars)
    assert path.expandenv("FOOBARA") == [os.environ["FOO1"], os.environ["FOO2"]]


@envsetup
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


@envsetup
@raises(OSError)
def test_which_oserror():
    path.which("<FOO>")


@envsetup
def test_which():
    fh, filename = tempfile.mkstemp(
        prefix="pyfarm-", suffix=".sh",
        dir=path.tempdir()
    )

    with open(filename, "w") as stream:
        pass

    os.environ["PATH"] = os.pathsep.join(
        [os.environ["PATH"], os.path.dirname(filename)]
    )
    basename = os.path.basename(filename)
    assert path.which(basename) == filename


@envsetup
def test_which_fullpath():
    thisfile = os.path.abspath(__file__)
    assert path.which(thisfile) == thisfile