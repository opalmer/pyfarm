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
import uuid
import inspect
from itertools import chain
from nose.tools import raises

from UserDict import IterableUserDict
from pyfarm.files.file import yamlDump
from pyfarm.config.core.find import configDirectories
from pyfarm.config.core import errors
from pyfarm.config.core.loader import Loader
from pyfarmnose.prepost import envsetup, mktmps


def test_subclasses():
    assert issubclass(Loader, IterableUserDict)

    for name, item in vars(errors).iteritems():
        if name != "PreferencesError" and inspect.isclass(item):
            assert issubclass(item, errors.PreferencesError)

    assert issubclass(errors.PreferencesError, Exception)


@raises(errors.PreferencesNotFoundError)
def test_raises_notfound():
    Loader("")


@raises(AssertionError)
def test_assert_invalid_dict_type():
    Loader(data="")


@raises(AssertionError)
def test_assert_invalid_typename_type():
    Loader(filename={})


@envsetup
def test_configuration_files():
    data = {}
    user, root, system = mktmps(3)

    for dirname in chain(*[user, root, system]):
        filepath = os.path.join(dirname, "config.yml")
        filedata = {str(uuid.uuid4()): str(uuid.uuid4())}
        print