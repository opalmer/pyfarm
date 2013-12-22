#!/usr/bin/env python

import os
import optparse
import shutil
import subprocess
import sys
import tempfile
from textwrap import dedent
from functools import partial

COMMAND_NOT_FOUND = object()
SUBPROJECT_DEPENDENCIES = (
    ("pyfarm-core", ()),
    ("pyfarm-models", (
        "pyfarm-core",
    )),
    ("pyfarm-master", (
        "pyfarm-core", "pyfarm-models"
    )),
    ("pyfarm-jobtypes", (
        "pyfarm-core",
    )),
    ("pyfarm-agent", (
        "pyfarm-core", "pyfarm-jobtypes"
    )),
    ("pyfarm-docs", (
        "pyfarm-core", "pyfarm-models", "pyfarm-master",
        "pyfarm-agent", "pyfarm-jobtypes"
    )))


def mkdir(path, noop=False):
    if noop:
      print "[dry-run] os.makedirs('%s')" % path

    elif not os.path.isdir(path):
        os.makedirs(path)
        print "created %s" % path


def run(command, silent=False, show_errors=True, noop=False, redirect=True):
    if noop:
        print "[dry-run] %s" % command
        return 0

    try:
        if not silent:
            print command

        kwargs = {}
        if redirect:
            kwargs.setdefault("stderr", subprocess.PIPE)
            kwargs.setdefault("stdout", subprocess.PIPE)

        proc = subprocess.Popen(list(command.split(" ")), **kwargs)
        stdout, stderr = proc.communicate()

        if redirect and proc.returncode and show_errors:
            print "error executing %s" % repr(command)
            if stdout:
                print "===== stdout ====="
                print stdout
                print "=================="

            if stderr:
                print "===== stderr ====="
                print stderr
                print "=================="

        return proc.returncode

    except OSError:
        return COMMAND_NOT_FOUND


def chdir(path, noop=False):
    if not noop:
        os.chdir(path)
    else:
        print "[dry-run] chdir %s" % path


parser = optparse.OptionParser(
    formatter=optparse.TitledHelpFormatter(),
    description=dedent("""
        %prog clones all of PyFarm's subprojects locally,
        create virtualenvs, and establishes an environment for use in
        development.

        NOTE:
        Please excercise caution when executing this
        script, it is generally untested and is best used on a clean working
        environment (such as when deploying to a remote machine""").strip())
parser.add_option(
    "-r", "--repo-url",
    default="https://github.com/pyfarm/",
    help="The root url which urls will be cloned from")
parser.add_option(
    "-c", "--clone-dir",
    help="The directory to clone the repos into")
parser.add_option(
    "-v", "--virtualenvs",
    help="The directory to create the various virtualenvs in")
parser.add_option(
    "-n", default=False, action="store_true", dest="noop",
    help="If provided, do not do anything to the file system")
options, args = parser.parse_args()

if not options.clone_dir:
    parser.print_help()
    parser.error("-c/--clone-dir is required")

if not options.virtualenvs:
    parser.print_help()
    parser.error("-v/--virtualenvs is required")

# check for virtualenv (the module)
try:
    import virtualenv
except ImportError:
    raise ImportError("`virtualenv` is not installed for %s" % sys.executable)

run = partial(run, noop=options.noop)
mkdir = partial(mkdir, noop=options.noop)
chdir = partial(chdir, noop=options.noop)

# check for pip
if run("git", show_errors=False, silent=True) is COMMAND_NOT_FOUND:
    raise OSError("command `git` not found")

# check for pip
if run("pip", show_errors=False, silent=True) is COMMAND_NOT_FOUND:
    raise OSError("command `pip` not found")

# check for virtualenv
if run("virtualenv", show_errors=False, silent=True) is COMMAND_NOT_FOUND:
    raise OSError("command `virtualenv` not found")

mkdir(options.clone_dir)
mkdir(options.virtualenvs)

# change the directory in the process git will always be
# outside of a repo
chdir(tempfile.gettempdir())

for repo, repo_deps in SUBPROJECT_DEPENDENCIES:
    clone_url = options.repo_url + repo + ".git"
    clone_path = os.path.join(options.clone_dir, repo)
    virtualenv_path = os.path.join(options.virtualenvs, repo)
    cmdargs = (clone_url, clone_path)

    if not os.path.isdir(clone_path):
        if run("git clone %s %s" % cmdargs, redirect=False):
            raise Exception("error cloning repo")

    else:
        chdir(clone_path)
        if run("git status", silent=True, show_errors=False):
            chdir(tempfile.gettempdir())
            shutil.rmtree(clone_path)
            if run("git clone %s %s" % cmdargs, redirect=False):
                raise Exception("error cloning repo")

    chdir(tempfile.gettempdir())

    venv_paths = virtualenv.path_locations(virtualenv_path)
    if not all(map(os.path.isdir, venv_paths)):
        if run("virtualenv %s" % virtualenv_path, redirect=False):
            raise Exception("error creating virtualenv")

    home, libs, include, bin = venv_paths

    if os.name == "nt":
        python = os.path.join(bin, "python.exe")
        pip = os.path.join(bin, "pip.exe")
    else:
        python = os.path.join(bin, "python")
        pip = os.path.join(bin, "pip")

    if not os.path.isfile(pip):
        raise Exception("ERROR: %s is not a file" % python)

    for dependent in repo_deps:
        dependent_path = os.path.join(options.clone_dir, dependent)
        if os.name == "nt":
            cmd = "%s install -e %s --egg" % (pip, dependent_path)
        else:
            cmd = "%s %s install -e %s --egg" % (python, pip, dependent_path)

        if run(cmd, redirect=False):
            raise Exception("failed to execute %s" % cmd)

    if os.name == "nt":
        cmd = "%s install -e %s --egg" % (pip, clone_path)
    else:
        cmd = "%s %s install -e %s --egg" % (python, pip, clone_path)

    if run(cmd, redirect=False):
        raise Exception("failed to execute %s" % cmd)
