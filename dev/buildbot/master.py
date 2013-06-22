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

from buildbot.changes.gitpoller import GitPoller
from buildbot.schedulers.basic import SingleBranchScheduler
from buildbot.schedulers.forcesched import ForceScheduler
from buildbot.process.factory import BuildFactory
from buildbot.steps.source.git import Git
from buildbot.steps.transfer import PropertiesFromVirtualEnvJson
from buildbot.steps.python import CreateVirtualEnvironment

# data we don't store in the public repo
from pyfarmbuildbotdata import (build_slaves, slavePortnum,
                                buildbotURL, auth_cfg)

c = BuildmasterConfig = {}

### Build Slaves
c["slavePortnum"] = slavePortnum
c["slaves"] = build_slaves

### Change Sources
c["change_source"] = [
    GitPoller("https://github.com/opalmer/pyfarm",
              workdir="gitpoller-master", branch="master", pollInterval=60),
]

### Schedulers
global_builders = ["runtests"]
c["schedulers"] = [
    SingleBranchScheduler(name="all",
                          change_filter=filter.ChangeFilter(branch='master'),
                          treeStableTimer=None,
                          builderNames=global_builders),
    ForceScheduler(name="force", builderNames=global_builders)
]

### Builders
step_git = Git(repourl="https://github.com/opalmer/pyfarm", mode="incremental")

builders = {}
# for python_version in ("2.5", "2.6", "2.7"):
python_version = "2.7"
builder = BuildFactory()
builder.addStep(step_git)
builder.addStep(CreateVirtualEnvironment(python_version))
builder.addStep(PropertiesFromVirtualEnvJson())
# builder.addStep(PIPInstall("2.7"))
# builder.addStep(NoseTest("2.7")) # TODO: R/D, someone may already have a nose class
# builder.addStep(DocGeneration("2.7")) # TODO: subclass sphinx
# builder.addStep(PyLint("2.7")) # TODO: sublcass PyLint base class
builders[python_version] = builder


c["builders"] = [

]

### Project Information
c["title"] = "PyFarm"
c["titleURL"] = "https://pyfarm.net"
c["buildbotURL"] = buildbotURL
