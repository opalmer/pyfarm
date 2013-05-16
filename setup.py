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

import os
import sys
import ast
import shutil
import setuptools
from distutils.core import setup
from os.path import abspath, dirname, join
from distutils.command.clean import clean as _clean

os.environ['PYFARM_SETUP_RUNNING'] = 'True'

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
    module = ast.parse(stream.read(), stream.name)

for obj in module.body:
    if isinstance(obj, ast.Assign) and obj.targets[0].id == "__version__":
        parsed_version = map(lambda num: num.n, obj.value.elts)
    elif isinstance(obj, ast.Assign) and obj.targets[0].id == "__author__":
        author = obj.value.s

assert isinstance(parsed_version, list), "did not find __version__"
assert isinstance(author, basestring), "did not find __author__"

ETC_DIRS = (os.path.join('etc', 'default'), )
PY_VERSION_INFO = sys.version_info
PY_MAJOR, PY_MINOR, PY_MICRO = PY_VERSION_INFO[0:3]

def requirements():
    """
    generates a list of requirements depending on python version
    and operating system
    """
    requires = []

    # split out versioned/unversioned requirements for easier
    # maintenance
    unversioned_requires = [
        'zope.interface',
        'nose',
        'appdirs',
        'colorama',
        'PyYaml'
    ]

    # if os.environ.get('READTHEDOCS', None) != 'True':
    # unversioned_requires.append('PyYaml')

    versioned_requires = [
        'sphinx>=1.1',
        'Jinja2>=2.3',
        'twisted>=11',
        'txJSON-RPC<.4',
        'psutil>=0.6.0',
        'netifaces>=0.8',
        'sqlalchemy>=0.7.0'
    ]

    # NOTE: commenting this out for now since it's not as easy
    #       as adding the code below
#    # determine if we need to install PyQt or PySide
#    try:
#        import PyQt4
#    except ImportError:
#        try:
#            import PySide
#        except ImportError:
#            unversioned_requires.append('PySide')

    try:
        import json

    except ImportError:
        unversioned_requires.append('simplejson')

    # determine if we need to install ordereddict
    try:
        from collections import OrderedDict
    except ImportError:
        unversioned_requires.append('ordereddict')

    # determine if we need to install argparse
    try:
        import argparse
    except ImportError:
        unversioned_requires.append('argparse')

    # windows specific requirements
    if sys.platform.startswith("win"):
        unversioned_requires.append("pywin32")

    requires.extend(versioned_requires)
    requires.extend(unversioned_requires)

    return requires
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
        for root, dirs, files in os.walk('.'):
            join = lambda path: os.path.join(root, path)
            for dirname in map(join, filter(eggdir, dirs)):
                rm(dirname)

        # remove egg files
        eggfiles = lambda name: name.endswith(".egg")
        for root, dirs, files in os.walk('.'):
            join = lambda path: os.path.join(root, path)
            files[:] = filter(eggfiles, files)

            for filename in map(join, files):
                rm(filename)
    # end run
# end clean

def getetc():
    """returns the files to copy over from etc/"""
    results = []
    for dirname in ETC_DIRS:
        results.append((dirname, setuptools.findall(dirname)))

    return results
# end getetc

libdir = os.path.join('lib', 'python')
requires = requirements()

setup(
    name='pyfarm',
    version=".".join(map(str, parsed_version)),
    package_dir={'' : libdir},
    data_files=getetc(),
    packages=setuptools.find_packages(libdir),
    setup_requires=requires,
    install_requires=requires,
    url='http://pyfarm.net',
    license='Apache v2.0',
    author=author,
    author_email='',
    description='',
    scripts=setuptools.findall('bin'),
    cmdclass={'clean' : clean}
)
