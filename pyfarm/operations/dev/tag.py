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

import sys
import requests
import git
from fabric.api import local


def create_tag(git_repo_name, tag):
    print "checking for pending commits"
    repo = git.Repo(".")

    if repo.git.ls_files(m=True):
        raise ValueError("cannot create tag, there are local modifications")

    sys.stdout.write("searching for existing tags")

    tags = requests.get(
        "https://api.github.com/repos/pyfarm/%s/git/refs/tags" % git_repo_name
    ).json()

    for data in tags:
        if data["ref"].split("/")[-1] == tag:
            raise NameError("tag already exists: %s" % tag)

        sys.stdout.write(".")
        sys.stdout.flush()
    print

    local("git tag %s && git push --tags" % tag)
