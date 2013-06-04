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

from __future__ import with_statement

import os
import sys
from StringIO import StringIO

PY_MAJOR, PY_MINOR, PY_MICRO = sys.version_info[0:3]

# quite a few packages won't support anything lower so
# raise an exception instead of an odd failure later on
assert (PY_MAJOR, PY_MINOR) >= (2, 5), "Python 2.5 or higher is required"

from _ast import *
try:
    from ast import parse
except ImportError:
    parse = lambda source, filename: compile(source, filename, "exec", PyCF_ONLY_AST)

import shutil
import setuptools
from distutils.core import setup
from os.path import abspath, dirname, join
from distutils.command.clean import clean as _clean

os.environ["PYFARM_SETUP_RUNNING"] = "True"

cwd = os.path.dirname(os.path.abspath(__file__))

initpy = abspath(join(dirname(__file__), "pyfarm", "__init__.py"))
author = None
parsed_version = None

with open(initpy, "r") as stream:
    module = parse(stream.read(), stream.name)

for obj in module.body:
    if isinstance(obj, Assign) and obj.targets[0].id == "__version__":
        parsed_version = map(lambda num: num.n, obj.value.elts)
    elif isinstance(obj, Assign) and obj.targets[0].id == "__author__":
        author = obj.value.s

assert isinstance(parsed_version, list), "did not find __version__"
assert isinstance(author, basestring), "did not find __author__"


def requirements(major, minor, develop=False, docs=True):
    """
    generates a list of requirements depending on the Python version,
    operating system, and develop keyword
    """
    requires = [
        "appdirs", "colorama", "PyYaml",
        "psutil", "netifaces", "sqlalchemy"
    ]

    # for local development
    if develop:
        requires.append("nose")

        if docs:
            requires.append("Jinja2==%s.%s" % (major, minor))
            requires.append("sphinx")

    # backports of modules introduced in 2.7
    if (major, minor) < (2, 7):
        requires.append("argparse")
        requires.append("ordereddict")
        requires.append("importlib")

    # higher than 2.5
    if (major, minor) > (2, 5):
        requires.append("zope.interface")
        requires.append("twisted")

    # 2.5 exclusive
    if (major, minor) == (2, 5):
        requires.append("simplejson")
        requires.append("zope.interface<=3.8.0")
        requires.append("twisted<=12.1")

    if sys.platform.startswith("win"):
        requires.append("pywin32")

    return list(requires)
# end requirements


class clean(_clean):
    """
    custom clean class which runs the standard clean then cleans up
    egg files and egg directories
    """
    def run(self):
        _clean.run(self)

        def rm(path):
            if os.path.isdir(path):
                shutil.rmtree(path)
            elif os.path.isfile(path):
                os.remove(path)
        # end rm

        # remove egg directories
        eggdir = lambda name: ".egg" in name or ".egg-info" in name
        for root, dirs, files in os.walk("."):
            join = lambda path: os.path.join(root, path)
            for dirname in map(join, filter(eggdir, dirs)):
                rm(dirname)

        # remove egg files
        eggfiles = lambda name: name.endswith(".egg")
        for root, dirs, files in os.walk("."):
            join = lambda path: os.path.join(root, path)
            files[:] = filter(eggfiles, files)

            for filename in map(join, files):
                rm(filename)
    # end run
# end clean

if __name__ == "__main__":
    # disable docs for some of the travis jobs
    build_docs = True
    if "TRAVIS_JOB_NUMBER" in os.environ and "BUILD_DOCS" not in os.environ:
        build_docs = False

    requires = requirements(
        PY_MAJOR, PY_MINOR,
        develop="install" not in sys.argv,
        docs=build_docs
    )

    # coveralls only supported in Python 2.5
    if (
        "TRAVIS" in os.environ
        and "BUILD_DOCS" not in os.environ
        and (PY_MAJOR, PY_MINOR) > (2, 5)
        and os.environ.get("TRAVIS_PYTHON_VERSION", "2.5") != "2.5"
    ):
        requires.append("coverage")
        requires.append("python-coveralls")

    # get all packages but make sure we don't include any
    # non-python code
    packages = filter(
        lambda name: name == "pyfarm" or name.startswith("pyfarm."),
        setuptools.find_packages(".")
    )

    # command line scripts used for operating pyfarm
    entry_points = {
        "console_scripts": [
            "pyfarm_master = pyfarm.script.master:run_master"
        ]
    }

    # readme for long_description
    # TODO: remove this, don't need the note after the first release
    readme = StringIO()
    print >> readme, ".. note::"
    print >> readme, "    This page is a placeholder only, no files have been uploaded yet."
    print >> readme, ""
    readme.write(open("README.rst", "r").read())
    readme = readme.getvalue()

    setup(
        name="pyfarm",
        version=".".join(map(str, parsed_version)),
        data_files=[("pyfarm-files", setuptools.findall("pyfarm-files"))],
        packages=packages,
        setup_requires=requires,
        install_requires=requires,
        url="http://pyfarm.net",
        license="Apache v2.0",
        author=author,
        author_email="development@pyfarm.net",
        description="A Python based distributed job system",
        long_description=readme,
        bugtrack_url="https://github.com/opalmer/pyfarm/issues",
        scripts=setuptools.findall("scripts"),
        cmdclass={"clean": clean},
        zip_safe=False,
        entry_points=entry_points,
        classifiers=[
            "Development Status :: 3 - Alpha",
            "Environment :: Other Environment",
            "Intended Audience :: Developers",
            "Intended Audience :: End Users/Desktop",
            "Intended Audience :: Science/Research",
            "Intended Audience :: Other Audience",
            "License :: OSI Approved :: Apache Software License",
            "Natural Language :: English",
            "Operating System :: OS Independent",
            "Programming Language :: Python :: 2 :: Only", # waiting on 3rd party packages
            "Programming Language :: Python :: 2.5",
            "Programming Language :: Python :: 2.6",
            "Programming Language :: Python :: 2.7",
            "Topic :: System :: Distributed Computing",
        ]
    )
