import os
from buildbot.status import html
from buildbot.buildslave import BuildSlave
from buildbot.steps.source.git import Git
from buildbot.steps.shell import ShellCommand
from buildbot.steps.slave import RemoveDirectory
from buildbot.steps.transfer import FileDownload
from buildbot.changes.gitpoller import GitPoller
from buildbot.process.factory import BuildFactory
from buildbot.schedulers.basic import AnyBranchScheduler
from buildbot.schedulers.forcesched import ForceScheduler
from buildbot.changes.filter import ChangeFilter
from buildbot.config import BuilderConfig

distutils_config = """
[build]
compiler=mingw32
""".strip()

distutils_config_path = os.path.abspath("disutils.cfg")
with open(distutils_config_path, "w") as stream:
    stream.write(distutils_config)


# because of the way the repo is setup on my local network
# this is necessary so the clone operations work
class GitNoRefs(Git):
    def __init__(self, *args, **kwargs):
        Git.__init__(self, *args, **kwargs)
        self.branch = self.branch.replace("refs/heads/", "")

    def startVC(self, branch, revision, patch):
        Git.startVC(self, branch, revision, patch)
        self.branch = self.branch.replace("refs/heads/", "")

# pyfarm-core factory
core_windows_factory = BuildFactory()
core_windows_factory.addSteps([
    GitNoRefs(
        repourl="git@vm-nas:/mnt/repos/pyfarm-core.git", mode="incremental"),
    ShellCommand(
        name="build virtualenv",
        flunkOnFailure=True,
        command=["virtualenv", "virtualenv"]),
    FileDownload(
        distutils_config_path, "virtualenv/Lib/distutils/distutils.cfg",
        flunkOnFailure=True,
        name="configure distutils"),
    ShellCommand(
        name="pip install .",
        flunkOnFailure=True,
        command=["virtualenv\\Scripts\\pip.exe", "install", ".", "--upgrade"]),
    ShellCommand(
        name="pip install nose",
        flunkOnFailure=True,
        command=["virtualenv\\Scripts\\pip.exe", "install", "nose"]),
    ShellCommand(
        name="nosetests",
        flunkOnFailure=True,
        command=["virtualenv\\Scripts\\nosetests.exe", "-v", "tests"]),
])


BuildmasterConfig = {
    "slavePortnum": 9989,
    "db": {"db_url": "postgresql://buildbot:123@127.0.0.1/buildbot"},
    "title": "PyFarm",
    "titleURL": "https://github.com/pyfarm",
    "buildbotURL": "http://buildmaster:8000/",
    "status": [html.WebStatus(http_port=8000)],
    "slaves":  [

        # TODO: add linux build slaves
        # TODO: add os x build slaves

        BuildSlave("pyfarm-core-xp", "123"),
        BuildSlave("pyfarm-master-xp", "123"),
        BuildSlave("pyfarm-agent-xp", "123"),
        BuildSlave("pyfarm-core-7", "123"),
        BuildSlave("pyfarm-master-7", "123"),
        BuildSlave("pyfarm-agent-7", "123")],
    "change_source": [
        GitPoller(
            "git@vm-nas:/mnt/repos/pyfarm-core.git",
            project="pyfarm-core",
            workdir="pollers/pyfarm-core",
            pollinterval=5, branches=True),
        GitPoller(
            "git@vm-nas:/mnt/repos/pyfarm-master.git",
            project="pyfarm-master",
            workdir="pollers/pyfarm-master",
            pollinterval=5, branches=True),
        GitPoller(
            "git@vm-nas:/mnt/repos/pyfarm-agent.git",
            project="pyfarm-agent",
            workdir="pollers/pyfarm-agent",
            pollinterval=5, branches=True)
    ],
    "schedulers": [
        AnyBranchScheduler(
            "pyfarm-core-7", treeStableTimer=None,
            builderNames=["pyfarm-core-7", "pyfarm-core-xp"],
            change_filter=ChangeFilter(project_re="^pyfarm-core.*"))
    ],
    "builders": [
        BuilderConfig(
            name="pyfarm-core-7", slavenames=["pyfarm-core-7"],
            factory=core_windows_factory),
        BuilderConfig(
            name="pyfarm-core-xp", slavenames=["pyfarm-core-xp"],
            factory=core_windows_factory)
    ]

}