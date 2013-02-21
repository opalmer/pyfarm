# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
import sys
import shutil
import setuptools
from distutils.core import setup
from distutils.command.clean import clean as _clean

os.environ['PYFARM_SETUP_RUNNING'] = 'True'

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.join(cwd, 'lib', 'python')
if root not in sys.path:
    sys.path.insert(0, root)

from pyfarm import __version__

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
    version=".".join(map(str, __version__)),
    package_dir={'' : libdir},
    data_files=getetc(),
    packages=setuptools.find_packages(libdir),
    setup_requires=requires,
    install_requires=requires,
    url='http://pyfarm.net',
    license='LGPL',
    author='Oliver Palmer',
    author_email='',
    description='',
    scripts=setuptools.findall('bin'),
    cmdclass={'clean' : clean}
)
