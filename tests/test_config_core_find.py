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
from nose.tools import raises

from pyfarmnose.prepost import envsetup, mktmp, mktmps
from pyfarm.config.core import find
from pyfarm.files.file import yamlDump

try:
    from itertools import product
except ImportError:
    from pyfarm.backports import product

VERSION = (1, 2, 3)

@raises(AssertionError)
@envsetup
def test_directories_version_error():
    find.configDirectories(version=0)


@raises(AssertionError)
@envsetup
def test_directories_user_error():
    find.configDirectories(user=0)


@raises(AssertionError)
@envsetup
def test_directories_system_error():
    find.configDirectories(system=0)


@raises(AssertionError)
@envsetup
def test_directories_roots_error():
    find.configDirectories(roots=0)


@envsetup
def test_output_types():
    assert isinstance(find.configDirectories(), list)
    assert isinstance(find.configFiles(""), list)


@envsetup
def test_directories_roots():
    roots = [mktmp() for _ in xrange(3)]
    assert roots == find.configDirectories(roots=roots)


@envsetup
def test_directories_roots_environ():
    roots = [mktmp() for _ in xrange(3)]
    os.environ["PYFARM_CFGROOT"] = os.pathsep.join(roots)
    assert roots == find.configDirectories(roots=roots)


@envsetup
def test_unversioned_directories():
    user, system, roots = mktmps(3)

    try_kwargs = (
        {},
        {"roots": roots},
        {"roots": roots, "user": user},
        {"roots": roots, "system": system},
        {"roots": roots, "user": user, "system": system},
    )
    expected_results = (
        [find.DEFAULT_CONFIG_ROOT],
        roots,
        user + roots,
        system + roots,
        user + system + roots
    )

    for kwargs, expected in zip(try_kwargs, expected_results):
        kwargs.setdefault("version", []) # just in case one was created somewhere
        assert expected == find.configDirectories(**kwargs)

@envsetup
def test_versioned_directories():
    user, system, roots = mktmps(3)
    version = [1, 2, 3]
    versions = find._versionSubdirs(version)

    try_kwargs = (
        {},
        {"roots": roots},
        {"roots": roots, "user": user},
        {"roots": roots, "system": system},
        {"roots": roots, "user": user, "system": system},
    )
    expected_results = (
        [find.DEFAULT_CONFIG_ROOT],
        roots,
        user + roots,
        system + roots,
        user + system + roots
    )

    # because of latency issues on travis-ci and because we don't care
    # about the directories existing, remove paths we do don't care
    # about as a filter
    all_config_dirs = user + system + roots
    not_a_tmp_path = lambda path: any(True for d in all_config_dirs if path.startswith(d))

    for kwargs, expected in zip(try_kwargs, expected_results):
        all_paths = []
        kwargs.setdefault("version", version)
        kwargs.setdefault("must_exist", False)

        for root, versiondir in product(expected, versions):
            path = os.path.join(root, versiondir) if versiondir else root
            if path not in all_paths:
                all_paths.append(path)

        filtered_config_dirs = filter(
            not_a_tmp_path,
            find.configDirectories(**kwargs)
        )

        if filtered_config_dirs:
            assert all_paths == filtered_config_dirs


@envsetup
def test_files_by_name():
    ymlname = "pyfarm_unittest.yml"
    user, system, roots = mktmps(3)

    try_kwargs = (
        {"roots": roots},
        {"roots": roots, "user": user},
        {"roots": roots, "system": system},
        {"roots": roots, "user": user, "system": system},
    )
    expected_results = (
        roots,
        user + roots,
        system + roots,
        user + system + roots
    )

    for kwargs, expected in zip(try_kwargs, expected_results):
        filenames = []

        for filepath in find.configDirectories(**kwargs):
            filename = os.path.join(filepath, ymlname)
            if filename not in filenames:
                yamlDump("", filename)
                if "TRAVIS" in os.environ:
                    for i in xrange(50):
                        if os.path.isfile(filename):
                            break
                    else:
                        raise OSError("file not dumped %s" % filename)

                filenames.append(filename)

        yaml_files = find.configFiles(
            ymlname,
            roots=kwargs.get("roots"),
            user=kwargs.get("user"),
            system=kwargs.get("system")
        )
        #
        assert yaml_files == filenames