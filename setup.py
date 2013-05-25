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

# quite a few packages won't support anything lower so
# raise an exception instead of an odd failure later on
assert sys.version_info[0:2] >= (2, 5), "Python 2.5 or higher is required"

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

initpy = abspath(
    join(
        dirname(__file__), "lib", "python", "pyfarm",
        "__init__.py"
    )
)
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

ETC_DIRS = (os.path.join("etc", "default"), )
PY_VERSION_INFO = sys.version_info
PY_MAJOR, PY_MINOR, PY_MICRO = PY_VERSION_INFO[0:3]

def requirements(major=None, minor=None):
    """
    generates a list of requirements depending on the Python version
    and operating system
    """
    requires = [
        "nose",
        "appdirs",
        "colorama",
        "PyYaml",
        "sphinx",
        "psutil",
        "netifaces",
        "sqlalchemy",
        "Jinja2==%s.%s" % (PY_MAJOR, PY_MINOR)
    ]
    major = PY_MAJOR if major is None else major
    minor = PY_MINOR if minor is None else minor

    if (major, minor) > (2, 5):
        requires.append("zope.interface")
        requires.append("twisted")

    if (major, minor) < (2, 7):
        requires.append("argparse")
        requires.append("ordereddict")

    if (major, minor) < (2, 6):
        requires.append("simplejson")

    if (major, minor) == (2, 5):
        # SyntaxError - "Exception as e"
        requires.append("zope.interface<=3.8.0")
        # support dropped - http://twistedmatrix.com/trac/ticket/5553
        requires.append("twisted<=12.1")

    if sys.platform.startswith("win"):
        requires.append("pywin32")

    return list(requires)
# end requirements


def getetc():
    """returns the files to copy over from etc/"""
    results = []
    for dirname in ETC_DIRS:
        results.append((dirname, setuptools.findall(dirname)))

    return results
# end getetc


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

if __name__ == '__main__':
    libdir = os.path.join("lib", "python")
    requires = requirements()

    setup(
        name="pyfarm",
        version=".".join(map(str, parsed_version)),
        package_dir={"": libdir},
        data_files=getetc(),
        packages=setuptools.find_packages(libdir),
        setup_requires=requires,
        install_requires=requires,
        url="http://pyfarm.net",
        license="Apache v2.0",
        author=author,
        author_email="",
        description="",
        scripts=setuptools.findall("bin"),
        cmdclass={"clean": clean}
    )
