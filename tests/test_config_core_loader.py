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
from UserDict import IterableUserDict

try:
    from collections import OrderedDict
except ImportError:
    from ordereddict import OrderedDict

from nose.tools import raises, with_setup, eq_


from pyfarm.files.file import yamlDump
from pyfarm.config.core.find import configFiles
from pyfarm.config.core import errors
from pyfarm.config.core.loader import Loader
from tests.pyfarmnose import (
    pretest_cleanup_env, posttest_cleanup_files, mktmps, mktmp
)


def prerun():
    pretest_cleanup_env()
    Loader._DATA.clear()

envsetup = with_setup(
    setup=prerun,
    teardown=posttest_cleanup_files
)


def _generatedata():
    data = OrderedDict()
    user, roots, system = mktmps(3)
    kwargs = {"user": user, "roots": roots, "system": system}

    for dirname in chain(*[user, roots, system]):
        filepath = os.path.join(dirname, "config.yml")
        filedata = {str(uuid.uuid4()): str(uuid.uuid4()), "test" : True}
        yamlDump(filedata, filepath)
        data[filepath] = filedata

    return data, kwargs


def test_subclasses():
    eq_(issubclass(Loader, IterableUserDict), True)

    for name, item in vars(errors).iteritems():
        if name != "PreferencesError" and inspect.isclass(item):
            eq_(issubclass(item, errors.PreferencesError), True)

    eq_(issubclass(errors.PreferencesError, Exception), True)


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
def test_load_files():
    data, kwargs = _generatedata()
    config = Loader("config.yml", **kwargs)
    eq_(config.files, configFiles("config.yml", **kwargs))


@envsetup
def test_data():
    uuid_data = {}
    data, kwargs = _generatedata()
    map(uuid_data.update, data.itervalues())
    config = Loader("config.yml", **kwargs)

    for key, value in config.iteritems():
        eq_(uuid_data[key], value)


@envsetup
def test_where_unique():
    dirs = [mktmp() for _ in xrange(3)]
    filenames = [os.path.join(d, "config.yml") for d in dirs]

    # generate unique data
    data = OrderedDict()
    for name in filenames:
        d = {str(uuid.uuid4()): str(uuid.uuid4())}
        data[yamlDump(d, name)] = d

    config = Loader("config.yml", roots=dirs)
    for filename, data in data.iteritems():
        eq_(config.where(data.keys()[0])[0], filename)


@envsetup
def test_where_nonunique():
    dirs = [mktmp() for _ in xrange(3)]
    filenames = [os.path.join(d, "config.yml") for d in dirs]

    # generate non-unique data
    data = OrderedDict()
    for name in filenames:
        d = {"test": str(uuid.uuid4())}
        data[yamlDump(d, name)] = d

    config = Loader("config.yml", roots=dirs)
    eq_(config.where("test"), config.files)


@envsetup
def test_where_unknown():
    dirs = [mktmp() for _ in xrange(3)]
    filenames = [os.path.join(d, "config.yml") for d in dirs]

    # generate non-unique data
    data = OrderedDict()
    for name in filenames:
        d = {}
        data[yamlDump(d, name)] = d

    config = Loader("config.yml", roots=dirs)
    eq_(config.where("foo"), [])


@envsetup
def test_which_unique():
    dirs = [mktmp() for _ in xrange(3)]
    filenames = [os.path.join(d, "config.yml") for d in dirs]

    # generate unique data
    data = OrderedDict()
    for name in filenames:
        d = {str(uuid.uuid4()): str(uuid.uuid4())}
        data[yamlDump(d, name)] = d

    config = Loader("config.yml", roots=dirs)
    for filename, data in data.iteritems():
        eq_(config.which(data.keys()[0]), filename)


@envsetup
def test_which_nonunique():
    dirs = [mktmp() for _ in xrange(3)]
    filenames = [os.path.join(d, "config.yml") for d in dirs]

    # generate non-unique data
    data = OrderedDict()
    for name in filenames:
        d = {"test": str(uuid.uuid4())}
        data[yamlDump(d, name)] = d

    config = Loader("config.yml", roots=dirs)
    eq_(config.which("test"), config.files[-1])


@envsetup
def test_which_unknown():
    dirs = [mktmp() for _ in xrange(3)]
    filenames = [os.path.join(d, "config.yml") for d in dirs]

    # generate non-unique data
    data = OrderedDict()
    for name in filenames:
        d = {}
        data[yamlDump(d, name)] = d

    config = Loader("config.yml", roots=dirs)
    eq_(config.which("foo"), None)


@envsetup
def test_get_override():
    dirs = [mktmp() for _ in xrange(3)]
    filenames = [os.path.join(d, "config.yml") for d in dirs]

    # generate non-unique data
    data = OrderedDict()
    for name in filenames:
        d = {"test": str(uuid.uuid4())}
        data[yamlDump(d, name)] = d
        last_value = d.values()[0]

    config = Loader("config.yml", roots=dirs)
    eq_(config.get("test"), last_value)


@envsetup
def test_get_dot_syntax():
    dirs = [mktmp() for _ in xrange(3)]
    filenames = [os.path.join(d, "config.yml") for d in dirs]

    # generate non-unique data
    data = OrderedDict()
    for name in filenames:
        last_value = str(uuid.uuid4())
        d = {"test": {"foo": last_value}}
        data[yamlDump(d, name)] = d

    config = Loader("config.yml", roots=dirs)
    eq_(config.get("test.foo"), last_value)


@envsetup
def test_get_unknown():
    config = Loader("", data={}, load=False)
    eq_(config.get("", -1), -1)


@envsetup
def test_get_known():
    v = str(uuid.uuid4())
    config = Loader("", data={"test": v}, load=False)
    eq_(config.get("test"), v)


@raises(KeyError)
@envsetup
def test_setitem_postinstance():
    config = Loader("", data={}, load=False)
    config["foo"] = True
    config["foo"]


@raises(KeyError)
@envsetup
def test_supdate_postinstance():
    config = Loader("", data={}, load=False)
    config.update({"foo": True})
    config["foo"]