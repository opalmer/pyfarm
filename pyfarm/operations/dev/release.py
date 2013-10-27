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
Operations specific releasing a version of a package

.. note::
    these scripts are quick fixes to some workflow needs, by no means are the
    a general representation of code quality
"""

from __future__ import with_statement

import re
import sys
import shutil
import argparse
from os.path import isdir

import git
import requests
from fabric.api import local

from pyfarm.operations.dev.tag import create_tag

RE_LOCAL_VERSION = re.compile("^    version=\"(.*)\".*$")
RE_PY_PACKAGE_NAME = re.compile("^    name=\"(.*)\".*$")


def main():
    if not isdir(".git"):
        raise OSError(".git directory not located")

    parser = argparse.ArgumentParser()
    parser.add_argument("version", nargs="?")
    parsed = parser.parse_args()

    local_version = None
    python_package_name = None
    pypi_version = None
    latest_git_tag = None

    # read local version and package name
    with open("setup.py", "r") as setup:
        for line in setup:
            match = RE_LOCAL_VERSION.match(line)
            if match is not None:
                local_version = match.group(1)

            match = RE_PY_PACKAGE_NAME.match(line)
            if match is not None:
                python_package_name = match.group(1)

    assert python_package_name is not None
    assert local_version is not None

    # get pypi version
    pypi = requests.get(
        "https://pypi.python.org/pypi/%s/json" % python_package_name)
    exists_on_pypi = pypi.status_code == 200

    if exists_on_pypi:
        pypi_version = pypi.json()["info"]["version"]

    # retrieve the tags
    git_repo_name = python_package_name.replace(".", "-")
    tags = requests.get(
        "https://api.github.com/repos/pyfarm/%s/git/refs/tags" % git_repo_name
    ).json()
    latest_git_tag = tags[-1]["ref"].split("/")[-1]

    # print some info
    print "Python Package Name: %s" % python_package_name
    print "      local version: %s" % local_version
    print "    version on PyPI: %s" % pypi_version
    print " last tag on github: %s" % latest_git_tag

    if parsed.version:
        should_continue = None
        while should_continue not in ("Y", "n"):
            should_continue = raw_input(
                "create release %s [Y/n] ? " % parsed.version)

        if should_continue != "Y":
            print "Quit!"
            sys.exit()


    create_tag(git_repo_name, parsed.version)
    local("python setup.py sdist upload")

    print "current local version: %s" % local_version

    new_local_version = None
    while True:
        new_local_version = raw_input("new local version -> ").strip()
        if raw_input("new version is %s? [Y/n]" % repr(new_local_version)) == "Y":
            break

    assert " " not in new_local_version
    with open("setup.py", "r") as setup:
        with open("setup.py.new", "w") as new_setup:
            new_setup.write(
                setup.read().replace(local_version, new_local_version))

    shutil.move("setup.py", ".setup.py.last")
    shutil.move("setup.py.new", "setup.py")

    repo = git.Repo(".")

    diff = repo.git.diff()
    if diff:
        should_commit = None
        print diff
        while should_commit not in ("Y", "n"):
            should_commit = raw_input("commit changes above? [Y/n] ? ")

        if should_continue == "Y":
            local('git ci -am "post-release commit" && git push')
