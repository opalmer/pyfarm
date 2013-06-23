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
from buildbot.changes.gitpoller import GitPoller
from buildbot.schedulers.basic import SingleBranchScheduler
from buildbot.schedulers.forcesched import ForceScheduler
from buildbot.process.factory import BuildFactory
from buildbot.steps.source.git import Git
from buildbot.steps.transfer import PropertiesFromVirtualEnvJson
from buildbot.steps.python import CreateVirtualEnvironment
from buildbot.steps.transfer import FileDownload
from buildbot.process.properties import Property
from buildbot.changes import filter as _filter
from buildbot.config import BuilderConfig
from buildbot.status import html
from buildbot.status.web import authz, auth

# data we don't store in the public repo
from pyfarmbuildbotdata import (build_slaves, slavePortnum, slave_mapping,
                                buildbotURL, authz_cfg, web_status_port,
                                dbconfig)

c = BuildmasterConfig = {}

### Build Slaves
c["slavePortnum"] = slavePortnum
c["slaves"] = build_slaves

### Change Sources
c["change_source"] = [
    GitPoller("https://github.com/opalmer/pyfarm",
              workdir="gitpoller-master", branch="master", pollInterval=60)
]

### Builders

build_factories = {}
# for python_version in ("2.5", "2.6", "2.7"):
for python_version in ("2.7", ):
    build_factory = BuildFactory()

    # repo checkout
    build_factory.addStep(
        Git(repourl="https://github.com/opalmer/pyfarm", mode="incremental"))

    # send virtualenv creation to slave
    build_factory.addStep(
        FileDownload(os.path.join("slave_scripts", "create_virtualenv.py"),
                     "create_virtualenv.py"))

    # run virtualenv creation script
    build_factory.addStep(
        CreateVirtualEnvironment(python_version))

    # retrieve the json it create and us it to set properties
    build_factory.addStep(
        PropertiesFromVirtualEnvJson(Property("virtualenv_slave_json")))

    # builder.addStep(FileUpload(PropertiesFromVirtualEnvJson(Property("virtualenv_json"))))
    # builder.addStep(PIPInstall("2.7"))
    # builder.addStep(NoseTest("2.7")) # TODO: R/D, someone may already have a nose class
    # builder.addStep(DocGeneration("2.7")) # TODO: subclass sphinx
    # builder.addStep(PyLint("2.7")) # TODO: sublcass PyLint base class
    build_factories[python_version] = build_factory

# c["builders"] = []
# for slave_group_name, slaves in slave_mapping.iteritems():
c["builders"] = [
    BuilderConfig(name="Python 2.7",
                  slavenames=[
                      slave.slavename for slave in slave_mapping["2.7"]],
                  factory=build_factories["2.7"])
]

### Schedulers
builder_names = ["Python 2.7"]
c["schedulers"] = [
    SingleBranchScheduler(name="all",
                          change_filter=_filter.ChangeFilter(branch='master'),
                          treeStableTimer=None,
                          builderNames=builder_names),
    ForceScheduler(name="force", builderNames=builder_names)
]

c["status"] = [
    html.WebStatus(http_port=web_status_port, authz=authz_cfg)]

### Project Information
c["title"] = "PyFarm"
c["titleURL"] = "https://pyfarm.net"
c["buildbotURL"] = buildbotURL
c["db"] = dbconfig