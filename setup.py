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

import sys
assert sys.version_info[0:2] >= (2, 5), "Python 2.5 or higher is required"

from os.path import isfile
from setuptools import setup

install_requires = ["fabric", "gitpython"]
if sys.version_info[0:2] < (2, 7):
    install_requires.extend(["argparse"])

if sys.version_info[0:2] == (2, 5):
    install_requires.append("requests==0.10.0")
else:
    install_requires.append("requests")

if isfile("README.rst"):
    with open("README.rst", "r") as readme:
        long_description = readme.read()
else:
    long_description = ""

setup(
    name="pyfarm.operations",
    version="0.7.0-dev0",
    packages=["pyfarm",
              "pyfarm.operations"],
    namespace_packages=["pyfarm"],
    entry_points={
        "console_scripts":[
            "pyfarm-dev-release = pyfarm.operations.dev.release:main",
            "pyfarm-dev-tags = pyfarm.operations.dev.tag:tags_main"]},
    install_requires=install_requires,
    url="https://github.com/pyfarm/pyfarm",
    license="Apache v2.0",
    author="Oliver Palmer",
    author_email="development@pyfarm.net",
    description="The root repository of PyFarm containing operations and"
                "deployment code",
    long_description=long_description,
    classifiers=[
        "Development Status :: 2 - Pre-Alpha",
        "Environment :: Other Environment",
        "Intended Audience :: Developers",
        "License :: OSI Approved :: Apache Software License",
        "Natural Language :: English",
        "Operating System :: OS Independent",
        "Programming Language :: Python :: 2 :: Only",  # (for now)
        "Programming Language :: Python :: 2.5",
        "Programming Language :: Python :: 2.6",
        "Programming Language :: Python :: 2.7",
        "Topic :: System :: Distributed Computing"])