#!/usr/bin/env python
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
import nose
import uuid
import yaml
import shutil
import random
import inspect
import tempfile
from UserDict import IterableUserDict

from pyfarm import preferences
from importlib import import_module
from pyfarm.datatypes.backports import OrderedDict
from pyfarm.preferences.base.baseclass import Loader, Preferences
from pyfarm.preferences.base.errors import PreferenceLoadError
from pyfarm import __version__, PYFARM_ETC

# location where we'll be saving our test data
TMP_TEST_DIRS = []
TEST_RANGE = range(2)
for i in TEST_RANGE:
    dirname = os.path.join(
        tempfile.gettempdir(),
        "_".join([
            "pyfarm",
            os.path.basename(__file__).split(os.extsep)[0],
            str(uuid.uuid4())
    ]))
    TMP_TEST_DIRS.append(dirname)

    # create the test directories
    if os.path.isdir(dirname):
        shutil.rmtree(dirname, ignore_errors=False)
    os.makedirs(dirname)

TEST_DATA = None
TEST_FILENAME = None
TEST_FILEPATH = None
CONFIGDIRS = Loader.configdirs[:]

def maketestdict():
    s = lambda : str(uuid.uuid4())
    i = lambda : random.randint(0, 500000000)
    d = lambda : random.choice([True, False, None, s(), i()])
    callables = (s, i, d)
    choices = [
        random.choice(callables)() for x in xrange(random.randint(3, 20))
    ]

    rd = lambda : {
        s() : d(),
        s() : d(),
        s() : d(),
        s() : choices,
        s() : d(),
        s() : d(),
    }

    out = rd()
    rdd = out[s()] = rd()
    rddd = rdd[s()] = rd()
    rddd[s()] = rd()
    return out
# end maketestdict

def prerun():
    global TEST_DATA, TEST_FILEPATH, TEST_FILENAME, CONFIGDIRS

    # Select a random base directory.  If there's something
    # wrong with the search used by Loader then doing this WILL
    # produce a failure eventually.
    testdir = random.choice(TMP_TEST_DIRS)

    # create filename for this test
    TEST_FILENAME = str(uuid.uuid4())
    TEST_FILEPATH = os.path.join(testdir, TEST_FILENAME + Loader.extension)
    assert not os.path.isfile(TEST_FILEPATH), "file should not exist"

    # write the test data
    with open(TEST_FILEPATH, 'w') as stream:
        data = maketestdict()
        yaml.dump(data, stream)
        TEST_DATA = data

    # manipulate configdirs so our test file should
    # be picked up at some point
    configdirs = list(Loader.configdirs)

    for path in TMP_TEST_DIRS:
        item = random.choice(configdirs)
        try:
            index = Loader.configdirs.index(item)
        except ValueError:
            index = -1
        configdirs.insert(index, path)

    Loader.configdirs = tuple(configdirs)
# end prerun

def postrun():
    Loader.configdirs = CONFIGDIRS
# end postrun

@nose.with_setup(setup=prerun, teardown=postrun)
def test_load():
    # check some internal data
    assert isinstance(Loader.configdirs, tuple)
    assert Loader.configdirs != CONFIGDIRS
    assert True in map(os.path.isdir, Loader.configdirs)

    # check for expected error
    try:
        Loader('<does not exist>')
    except PreferenceLoadError, e:
        assert "failed to find any" in e.args[0]

    # load data and ensure the same file we dumped is loaded
    loaded = Loader(TEST_FILENAME)
    assert len(loaded.filenames) == 1
    assert loaded.filenames[0] == TEST_FILEPATH

    # test the data we loaded
    dataA = tuple(sorted(TEST_DATA.iteritems()))
    dataB = tuple(sorted(loaded.iteritems()))
    assert dataA == dataB
# end test_load

@nose.with_setup(setup=prerun, teardown=postrun)
def test_loadmultiple():
    name = str(uuid.uuid4())
    data = maketestdict()
    filenames = []
    random_data = []
    file_data = {}

    for path in TMP_TEST_DIRS:
        filename = os.path.join(path, name + Loader.extension)

        with open(filename, 'w') as stream:
            filenames.append(stream.name)
            d = data.copy()
            r = (str(uuid.uuid4()), str(uuid.uuid4()))
            d[r[0]] = r[1]
            file_data[r[0]] = stream.name
            random_data.append(r)
            yaml.dump(d, stream)

    loader = Loader(name)
    assert sorted(loader.filenames) == sorted(filenames)

    # select a key which should be in both files
    new_keys = [ key[0] for key in random_data ]
    key = random.choice(loader.keys())
    while key in new_keys:
        key = random.choice(loader.keys())

    # common keys
    where = loader.where(key, all=True)
    assert len(where) == len(TMP_TEST_DIRS)
    assert sorted(where) == sorted(filenames)

    for key in new_keys:
        assert key in loader
        where = loader.where(key, all=True)
        assert len(where) == 1
        assert where[0] == file_data[key]
# end test_loadmultiple

def test_versions():
    expected = (
        ".".join(map(str, __version__)),
        ".".join(map(str, __version__[0:2])),
        ""
    )
    assert isinstance(Loader.versions, tuple)
    assert Loader.versions == expected
# end test_versions

@nose.with_setup(setup=postrun)
def test_configdirs():
    expected = (
        Loader.config.user_data_dir,
        Loader.config.site_data_dir,
        PYFARM_ETC
    )
    assert isinstance(Loader.configdirs, tuple)
    assert Loader.configdirs == expected
# end test_configdirs

def test_subclass():
    assert issubclass(Loader, IterableUserDict)
    assert isinstance(Preferences, object)

    # test subclasses of custom preferences
    files = lambda name: "__" not in name and name != "base"
    dirname = os.path.dirname(preferences.__file__)
    names = filter(files, set((i.split(".")[0] for i in os.listdir(dirname))))
    skip = (
        "__package__", "__doc__", "pyfarm.logger.Logger",
        "ordereddict.OrderedDict", "Logger"
    )

    module_names = [ "%s.%s" % (preferences.__name__, name) for name in names ]
    for module in map(import_module, module_names):
        for name, obj in vars(module).iteritems():
            if name != "Preferences" and \
                name not in skip and \
                inspect.isclass(obj) and \
                not issubclass(obj, OrderedDict):
                assert issubclass(obj, Preferences)
# end test_subclass

if __name__ == '__main__':
    nose.run()