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

import re
from os.path import isfile

import requests

RE_LOCAL_VERSION = re.compile("^    version=\"(.*)\".*$")
RE_PY_PACKAGE_NAME = re.compile("^    name=\"(.*)\".*$")


def info_from_setup():
    """returns the python package and local version from setup.py"""
    local_version = None
    python_package_name = None
    assert isfile("setup.py")

    with open("setup.py", "r") as setup:
        for line in setup:
            if local_version and python_package_name:
                break

            match = RE_LOCAL_VERSION.match(line)
            if match is not None:
                local_version = match.group(1)

            match = RE_PY_PACKAGE_NAME.match(line)
            if match is not None:
                python_package_name = match.group(1)

    assert python_package_name is not None
    assert local_version is not None
    return python_package_name, local_version


def get_latest_git_tag():
    """returns the latest tagfrom the github api"""
    tags = requests.get("%s/git/refs/tags" % get_github_api_url()).json()
    latest_git_tag = tags[-1]
    print latest_git_tag


def get_github_api_url():
    """returns the github api url for the package"""
    python_package_name, local_version = info_from_setup()
    repo_name = python_package_name.replace(".", "-")
    return "https://api.github.com/repos/pyfarm/%s" % repo_name


def get_pypi_json_api_url():
    python_package_name, local_version = info_from_setup()
    return "https://pypi.python.org/pypi/%s/json" % python_package_name
