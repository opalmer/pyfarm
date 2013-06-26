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
import uuid
import stat
import tempfile

from nose.tools import raises, eq_
from nose.plugins.skip import SkipTest

from tests.pyfarmnose import mktmp, envsetup
from pyfarm.ext import files
from pyfarm.ext.files import TempFile, yamlLoad, yamlDump


@envsetup
def test_tempfile_delete():
    # travis seems to have issues with deleting
    # files on time, this should pass locally however
    if "TRAVIS" in os.environ:
        raise SkipTest

    with TempFile(delete=True) as s:
        eq_(os.path.isfile(s.name), True)

    max_time = 30
    start = time.time()

    while time.time()-start <= max_time:
        if not os.path.isfile(s.name):
            return

        time.sleep(.1)

    assert False, "failed %s" % s.name


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
    eq_(os.path.dirname(dump_path), files.SESSION_DIRECTORY)


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

@envsetup
def test_tempdir_session():
    sessiondir = files.tempdir(unique=False)
    eq_(files.tempdir(unique=False), sessiondir)
    eq_(files.tempdir(unique=False), files.SESSION_DIRECTORY)
    eq_(sessiondir, files.SESSION_DIRECTORY)


@envsetup
def test_tempdir_envvar():
    os.environ["PYFARM_TMP"] = mktmp()
    eq_(files.tempdir(respect_env=True), os.environ["PYFARM_TMP"])
    eq_(files.tempdir(respect_env=False), files.SESSION_DIRECTORY)


@envsetup
def test_tempdir_unique():
    eq_(
        files.tempdir(respect_env=False, unique=True)
        != files.tempdir(respect_env=False, unique=True),
        True
    )


@envsetup
def test_tempdir_mode():
    st_mode = os.stat(files.tempdir(unique=True)).st_mode
    eq_(stat.S_IMODE(st_mode), files.DEFAULT_PERMISSIONS)
    mymode = stat.S_IRWXU
    st_mode = os.stat(files.tempdir(unique=True, mode=mymode)).st_mode
    eq_(stat.S_IMODE(st_mode), mymode)


@envsetup
def test_expandpath():
    os.environ["FOO"] = "foo"
    joined_files = os.path.join("~", "$FOO")
    expected = os.path.expanduser(os.path.expandvars(joined_files))
    eq_(files.expandpath(joined_files), expected)


@envsetup
@raises(EnvironmentError)
def test_expandenv_raise_enverror():
    files.expandenv(str(uuid.uuid4()))


@envsetup
@raises(ValueError)
def test_expandenv_raise_valuerror():
    var = str(uuid.uuid4())
    os.environ[var] = ""
    files.expandenv(var)


@envsetup
def test_expandenv_files_validation():
    envvars = {
        "FOO1": mktmp(), "FOO2": mktmp(),
        "FOO3": "<unknown_foo>",
        "FOOBARA": os.pathsep.join(["$FOO1", "$FOO2", "$FOO3"])
    }
    os.environ.update(envvars)
    eq_(
        files.expandenv("FOOBARA"),
        [os.environ["FOO1"], os.environ["FOO2"]]
    )


@envsetup
def test_expandenv_files_novalidation():
    envvars = {
        "FOO4": mktmp(), "FOO5": mktmp(),
        "FOO6": "<unknown_foo>",
        "FOOBARB": os.pathsep.join(["$FOO5", "$FOO4", "$FOO6"])
    }
    os.environ.update(envvars)
    expanded = files.expandenv("FOOBARB", validate=False)
    eq_(
        expanded,
        [os.environ["FOO5"], os.environ["FOO4"], os.environ["FOO6"]]
    )


@envsetup
@raises(OSError)
def test_which_oserror():
    files.which("<FOO>")


@envsetup
def test_which():
    fh, filename = tempfile.mkstemp(
        prefix="pyfarm-", suffix=".sh",
        dir=files.tempdir()
    )

    with open(filename, "w") as stream:
        pass

    os.environ["PATH"] = os.pathsep.join(
        [os.environ["PATH"], os.path.dirname(filename)]
    )
    basename = os.path.basename(filename)
    eq_(files.which(basename), filename)


@envsetup
def test_which_fullfiles():
    thisfile = os.path.abspath(__file__)
    eq_(files.which(thisfile), thisfile)