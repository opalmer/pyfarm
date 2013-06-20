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
from buildbot.steps.shell import ShellCommand

# data we don't store in the public repo
from pyfarmbuildbotdata import build_slaves, slavePortnum, buildbotURL, auth_cfg

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

py27builder = BuildFactory()
py27builder.addStep(step_git)

# TODO: create virtual environment in temp directory using
#       virtualenv.create_environment
# TODO: before running pip source the activate_this script then use the pip api,
#       it may also be possible to use python -c to do this too (NOTE: be sure
#       to use --download-cache)
# py27builder.addStep(CreateVirtualEnvironment(python="python2.5"))
# py27builder.addStep(PIPInstall())
# py27builder.addStep(VirtualEnvShellCommand(command=[...])
# py27builder.addStep(ShellCommand(command=["pip", "install", "-e", "."]))


c["builders"] = [

]

### Project Information
c["title"] = "PyFarm"
c["titleURL"] = "https://pyfarm.net"
c["buildbotURL"] = buildbotURL
