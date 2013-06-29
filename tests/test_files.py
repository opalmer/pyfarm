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

from nose.tools import raises

from utcore import TestCase, skip_on_ci
from pyfarm.ext import files
from pyfarm.ext.files import TempFile, yamlLoad, yamlDump


class TmpFile(TestCase):
    @skip_on_ci
    def test_delete(self):
        with TempFile(delete=True) as s:
            self.assertTrue(os.path.isfile(s.name))

        max_time = 30
        start = time.time()

        while time.time()-start <= max_time:
            if not os.path.isfile(s.name):
                return

            time.sleep(.1)

        self.fail("failed %s" % s.name)

    def test_nodelete(self):
        with TempFile(delete=False) as s:
            self.assertTrue(os.path.isfile(s.name))

        self.assertTrue(os.path.isfile(s.name))

    def test_dirname(self):
        d = self.mktempdir()
        with TempFile(root=d, delete=True) as s:
            self.assertEqual(os.path.dirname(s.name), d)

    def test_basename(self):
        d = self.mktempdir()

        with TempFile(prefix="foo", suffix=".txt", root=d, delete=True) as s:
            base = os.path.basename(s.name)
            self.assertTrue(base.startswith("foo"))
            self.assertTrue(base.endswith(".txt"))

    def test_tempfile_basename(self):
        d = self.mktempdir()
        with TempFile(prefix="foo", suffix=".txt", root=d, delete=True) as s:
            base = os.path.basename(s.name)
            self.assertTrue(base.startswith("foo"))
            self.assertTrue(base.endswith(".txt"))


class DumpYaml(TestCase):
    @raises(TypeError)
    def test_error(self):
        yamlDump("", lambda: None)

    def test_tmppath(self):
        dump_path = yamlDump("")
        self.assertTrue(dump_path.endswith(".yml"))
        self.assertEqual(os.path.dirname(dump_path), files.SESSION_DIRECTORY)

    def test_path(self):
        d = self.mktempdir()
        expected_dump_path = os.path.join(d, "foo", "foo.yml")
        dump_path = yamlDump("", path=expected_dump_path)
        self.assertTrue(os.path.isdir(os.path.dirname(expected_dump_path)))
        self.assertEqual(dump_path, expected_dump_path)


class LoadYaml(TestCase):
    @raises(TypeError)
    def test_error(self):
        yamlLoad(lambda: None)

    def test_path(self):
        data = os.environ.data.copy()
        dumped_path = yamlDump(data)
        self.assertEqual(yamlLoad(dumped_path), data)
        
    def test_stream(self):
        data = os.environ.data.copy()
        dumped_path = yamlDump(data)
        s = open(dumped_path, "r")
        self.assertEqual(yamlLoad(s), data)
        self.assertTrue(s.closed)


class TmpFile(TestCase):
    def test_session(self):
        sessiondir = files.tempdir(unique=False)
        self.assertEqual(files.tempdir(unique=False), sessiondir)
        self.assertEqual(files.tempdir(unique=False), files.SESSION_DIRECTORY)
        self.assertEqual(sessiondir, files.SESSION_DIRECTORY)

    def test_envvar(self):
        os.environ["PYFARM_TMP"] = self.mktempdir()
        self.assertEqual(files.tempdir(respect_env=True),
                         os.environ["PYFARM_TMP"])
        self.assertEqual(files.tempdir(respect_env=False),
                         files.SESSION_DIRECTORY)

    def test_unique(self):
        self.assertNotEqual(
            files.tempdir(respect_env=False, unique=True),
            files.tempdir(respect_env=False, unique=True))

    def test_mode(self):
        st_mode = os.stat(files.tempdir(unique=True)).st_mode
        self.assertEqual(stat.S_IMODE(st_mode), files.DEFAULT_PERMISSIONS)
        mode = stat.S_IRWXU
        st_mode = os.stat(files.tempdir(unique=True, mode=mode)).st_mode
        self.assertEqual(stat.S_IMODE(st_mode), mode)


class Expand(TestCase):
    def test_expandpath(self):
        os.environ["FOO"] = "foo"
        joined_files = os.path.join("~", "$FOO")
        expected = os.path.expanduser(os.path.expandvars(joined_files))
        self.assertEqual(files.expandpath(joined_files), expected)

    @raises(EnvironmentError)
    def test_raise_enverror(self):
        files.expandenv(str(uuid.uuid4()))

    @raises(ValueError)
    def test_raise_valuerror(self):
        var = str(uuid.uuid4())
        os.environ[var] = ""
        files.expandenv(var)

    def test_files_validation(self):
        envvars = {
            "FOO1": self.mktempdir(),
            "FOO2": self.mktempdir(),
            "FOO3": "<unknown_foo>",
            "FOOBARA": os.pathsep.join(["$FOO1", "$FOO2", "$FOO3"])
        }
        os.environ.update(envvars)
        self.assertEqual(
            files.expandenv("FOOBARA"),
            [os.environ["FOO1"], os.environ["FOO2"]]
        )

    def test_files_novalidation(self):
        envvars = {
            "FOO4": self.mktempdir(), 
            "FOO5": self.mktempdir(),
            "FOO6": "<unknown_foo>",
            "FOOBARB": os.pathsep.join(["$FOO5", "$FOO4", "$FOO6"])
        }
        os.environ.update(envvars)
        expanded = files.expandenv("FOOBARB", validate=False)
        self.assertEqual(
            expanded,
            [os.environ["FOO5"], os.environ["FOO4"], os.environ["FOO6"]]
        )


class Which(TestCase):
    @raises(OSError)
    def test_which_oserror(self):
        files.which("<FOO>")

    def test_path(self):
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
        self.assertEqual(files.which(basename), filename)

    def test_fullfiles(self):
        thisfile = os.path.abspath(__file__)
        self.assertEqual(files.which(thisfile), thisfile)