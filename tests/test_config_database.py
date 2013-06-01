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
from nose.tools import with_setup

from pyfarmnose import mktmp, pretest_cleanup_env, posttest_cleanup_files
from pyfarm.files.file import yamlDump
from pyfarm.config.database import Loader, DBConfig


def prerun():
    pretest_cleanup_env()
    d = mktmp()
    config_path = os.path.join(d, "database.yml")
    os.environ["PYFARM_TMP_DBDIR"] = d
    data = {
        "db1": {"database": ":memory:", "engine": "sqlite"},
        "db2": {"database": os.path.join(d, "db2.sql"), "engine": "sqlite"},
        "db3": {"database": "$PYFARM_TMP_DBDIR/db3.sql", "engine": "sqlite"},
        "configs": {"unittests-config": ["db1", "db2", "db3"]}
    }
    yamlDump(data, config_path)
    Loader._DATA[config_path] = data


setupenv = with_setup(
    setup=prerun,
    teardown=posttest_cleanup_files
)


@setupenv
def test_foo():
    config = DBConfig()
    print config.engine("unittests-config")