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

import nose
from nose.tools import raises

from core.prepost import mktmp, pretest_cleanup_env, posttest_cleanup_files
from pyfarm.files.stream import TempFile, ymlload, ymldump


setup = nose.with_setup(
    setup=pretest_cleanup_env,
    teardown=posttest_cleanup_files
)


@setup
@raises(ValueError)
def test_tempfile_error():
    TempFile(name="foo", suffix="foo")