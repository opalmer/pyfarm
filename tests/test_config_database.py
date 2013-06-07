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

# TODO: these tests should really be testing the **provided** configs, not random data

import os
from nose.tools import with_setup, eq_

from pyfarmnose import mktmp, pretest_cleanup_env, posttest_cleanup_files
from pyfarm.ext.config.database import Loader, DBConfig


def prerun():
    pretest_cleanup_env()
    d = mktmp()
    os.environ["PYFARM_TMP_DBDIR"] = d
    data = {
        "db": {"database": ":memory:", "engine": "sqlite"},
        "configs": {"unittests-config": "db"}
    }

    loader = Loader("database.yml")
    for filepath in loader._DATA.iterkeys():
        loader._DATA[filepath] = data


def postrun():
    Loader._DATA.clear()
    posttest_cleanup_files()


setupenv = with_setup(setup=prerun, teardown=postrun)


@setupenv
def test_url():
    config = DBConfig()
    eq_(config.url("unittests-config"), "sqlite:///:memory:")