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

import argparse

import git
import requests
from fabric.api import local

from pyfarm.operations.dev.utility import get_github_api_url


def local_tag_exists(tag, repo=None):
    """returns True if ``tag`` exists locally"""
    repo = repo or git.Repo(".")

    for t in repo.tags:
        if t.name == tag:
            return True

    return False


def remote_tag(tag):
    """returns the remote entry for the tag from github"""
    url = "%s/git/refs/tags" % get_github_api_url()
    for result in requests.get(url).json():
        try:
            if result["ref"] == "refs/tags/%s" % tag:
                return result
        except TypeError:
            return


def remote_tags():
    """generator producing a list of all remote tags"""
    url = "%s/git/refs/tags" % get_github_api_url()
    for result in requests.get(url).json():
        ref = result["ref"]
        version = ref.split("/")[-1]
        if version is not None:
            yield version


def create_tag(tag):
    """create ``tag``"""
    repo = git.Repo(".")

    if repo.git.ls_files(m=True):
        raise ValueError("cannot create tag, there are local modifications")

    if remote_tag(tag):
        print "remote tag %s exists" % tag
        return

    if not local_tag_exists(tag):
        local("git tag %s" % tag)
        local("git push --tags")
    else:
        print "local tag %s exists" % tag


def delete_tag(tag, remote=True):
    """delete the requested tag"""
    repo = git.Repo(".")

    for t in repo.tags:
        if t.name == tag:
            local("git tag -d %s " % tag, capture=True)
            break
    else:
        print "local tag %s does not exist" % repr(tag)

    if remote and remote_tag(tag):
        local("git push origin :refs/tags/%s" % tag)

    elif remote:
        print "remote tag %s does not exist" % repr(tag)


def tags_main():
    parser = argparse.ArgumentParser()
    parser.add_argument("tags", nargs="+")
    parser.add_argument("-d", "--delete", action="store_true")
    parser.add_argument("-c", "--create", action="store_true")
    parsed = parser.parse_args()

    if parsed.delete:
        map(delete_tag, parsed.tags)
    if parsed.create:
        map(create_tag, parsed.tags)

    if not parsed.delete and not parsed.create:
        parser.print_help()