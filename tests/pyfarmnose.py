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

"""
Functions used for the file :mod:`pyfarm.files` tests
"""

import os
import shutil
import tempfile
from fnmatch import filter as fnfilter
from nose.tools import with_setup

# NOTE: do not use pyfarm.ext for tests
from pyfarm.ext import files

CLEAN_ENV = os.environ.copy()


def pretest_cleanup_env():
    files.SESSION_DIRECTORY = None

    for varname, value in os.environ.copy().iteritems():
        if varname not in CLEAN_ENV:
            del os.environ[varname]
        elif os.environ[varname] != CLEAN_ENV[varname]:
            os.environ[varname] = CLEAN_ENV[varname]


def posttest_cleanup_files():
    pass


def mktmps(list_count, depth=3):
    results = []

    for i in xrange(list_count):
        results.append([mktmp() for _ in xrange(depth)])

    return results

if "BUILDBOT_UUID" in os.environ:
    PREFIX = "-".join([os.environ["BUILDBOT_UUID"],
                       files.DEFAULT_DIRECTORY_PREFIX])
else:
    PREFIX = files.DEFAULT_DIRECTORY_PREFIX

mktmp = lambda: tempfile.mkdtemp(prefix=PREFIX)
envsetup = with_setup(setup=pretest_cleanup_env, teardown=posttest_cleanup_files)