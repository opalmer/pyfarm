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
The configuration file for the buildbot master.

.. note::
    private information such as databasae and slave passwords is
    **not** a part of this file
"""

from __future__ import with_statement

import os
try:
    from itertools import product
except ImportError:
    def product(*args, **kwds):
        pools = map(tuple, args) * kwds.get('repeat', 1)
        result = [[]]
        for pool in pools:
            result = [x+[y] for x in result for y in pool]
        for prod in result:
            yield tuple(prod)

from buildbot.changes.gitpoller import GitPoller
from buildbot.schedulers.basic import SingleBranchScheduler
from buildbot.schedulers.forcesched import ForceScheduler
from buildbot.process.factory import BuildFactory
from buildbot.process.properties import WithProperties, Property
from buildbot.steps.source.git import Git
from buildbot.steps.shell import ShellCommand
from buildbot.steps.transfer import PropertiesFromVirtualEnvJson, FileDownload
from buildbot.steps.virtenv import (CreateVirtualEnvironment, WrappedCall,
                                    VirtualEnvSphinx, VirtualEnvPyLint)
from buildbot.changes import filter as _filter
from buildbot.config import BuilderConfig
from buildbot.status import html

# data we don't store in the public repo
from pyfarmbuildbotdata import (build_slaves, slavePortnum, slave_mapping,
                                buildbotURL, authz_cfg, web_status_port,
                                dbconfig)

PYTHON_VERSIONS = ("2.5", "2.6", "2.7")
DATABASES = ("sqlite", "postgres", "mysql")
PLATFORMS = ("linux", "windows", "mac")


### Project Information
c = BuildmasterConfig = {
    "title": "PyFarm",
    "titleURL": "https://pyfarm.net",
    "buildbotURL": buildbotURL,
    "db": dbconfig,
    "slavePortnum": slavePortnum,
    "slaves": build_slaves,
    "change_source": [
        GitPoller("https://github.com/opalmer/pyfarm",
                  workdir="gitpoller-master", branch="master",
                  pollInterval=60)],
    "status": [html.WebStatus(http_port=web_status_port, authz=authz_cfg)]
}

### Builders
build_factory = BuildFactory()

# repo checkout
build_factory.addStep(
    Git(repourl="https://github.com/opalmer/pyfarm", mode="incremental"))

# send virtualenv creation to slave
build_factory.addStep(
    FileDownload(os.path.join("slave_scripts", "create_virtualenv.py"),
                 "create_virtualenv.py",
                 name="download: create_virtualenv"))

# download the database configuration
build_factory.addStep(
    FileDownload(
        WithProperties("config/database_%s.yml", "database"),
        "pyfarm-files/config/database.yml",
        name="download: database config"))

# send a script that we can use to remove
# the virtual environment
build_factory.addStep(
    FileDownload(os.path.join("slave_scripts", "cleanup_virtualenv.py"),
                 "cleanup_virtualenv.py",
                 name="download: cleanup"))

# run virtualenv creation script
build_factory.addStep(
    CreateVirtualEnvironment(Property("python"),
                             name="virtualenv: create"))

# retrieve the json it create and us it to set properties
build_factory.addStep(
    PropertiesFromVirtualEnvJson(Property("virtualenv_slave_json"),
                                 name="virtualenv: properties"))

build_factory.addStep(
    WrappedCall(["pip", "install", "-e", "."],
                name="install: pyfarm"))

build_factory.addStep(
    WrappedCall(["pip", "install", "nose"],
                name="install: nose"))

build_factory.addStep(
    WrappedCall(["nosetests", "tests", "pyfarm",
                 "-s", "--verbose", "--with-doctest"],
                name="nosetest"))

build_factory.addStep(
    WrappedCall(["pip", "install", "pylint"],
                name="install: pylint"))

build_factory.addStep(VirtualEnvPyLint("pyfarm"))

build_factory.addStep(
    WrappedCall(["pip", "install", "sphinx"],
                name="install: sphinx"))

build_factory.addStep(VirtualEnvSphinx())

# cleanup
build_factory.addStep(
    ShellCommand(command=[Property("python"),
                          "cleanup_virtualenv.py",
                          Property("virtualenv_root")],
                 name="cleanup"))


# for slave_group_name, slaves in slave_mapping.iteritems():
c["builders"] = [
    BuilderConfig(name="python27_linux_sqlite",
                  slavenames=[slave.slavename
                              for slave in slave_mapping["2.7"]["linux"]],
                  factory=build_factory,
                  env={"BUILD_UUID": Property("virtualenv_uuid")},
                  properties={"database": "sqlite",
                              "python": "python2.7"}),
    BuilderConfig(name="python26_linux_sqlite",
                  slavenames=[slave.slavename
                              for slave in slave_mapping["2.6"]["linux"]],
                  factory=build_factory,
                  env={"BUILD_UUID": Property("virtualenv_uuid")},
                  properties={"database": "sqlite",
                              "python": "python2.6"})
]

### Schedulers
builder_names = ["python27_linux_sqlite"]
c["schedulers"] = [
    SingleBranchScheduler(name="all",
                          change_filter=_filter.ChangeFilter(branch="master"),
                          treeStableTimer=None,
                          builderNames=builder_names),
    ForceScheduler(name="force", builderNames=builder_names)]