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
import setuptools
from distutils.core import setup, DistutilsError

PY_VERSION_INFO = sys.version_info
PY_MAJOR, PY_MINOR, PY_MICRO = PY_VERSION_INFO[0:3]

def requirements():
    '''
    generates a list of requirements depending on python version
    and operating system
    '''
    requires = []

    # split out versioned/unversioned requirements for easier
    # maintenance
    unversioned_requires = [
        'zope.interface',
        'nose',
        'appdirs',
        'PyYaml',
        'colorama',
    ]
    versioned_requires = [
        'python>=2.5,python<=2.7',
        'twisted>=11,twisted<=12',
        'txJSON-RPC==0.3.0',
        'psutil>=0.6.0',
        'netifaces>=0.8',
        'sqlalchemy>=0.7.0'
    ]

    # determine if we need to install PyQt or PySide
    try:
        import PyQt4
    except ImportError:
        try:
            import PySide
        except ImportError:
            unversioned_requires.append('PySide')

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

    # add a couple of additional requirements for Python2.6
    if PY_MINOR == 6:

        unversioned_requires.extend(['ordereddict', 'argparse'])

    requires.extend(versioned_requires)
    requires.extend(unversioned_requires)

    return requires
# end requirements

def version():
    from pyfarm import __version__
    return ".".join(map(str, __version__))
# end version

setup(
    name='pyfarm',
    version=version(),
    packages=setuptools.find_packages(),
    setup_requires=requirements(),
    url='http://pyfarm.net',
    license='LGPL',
    author='Oliver Palmer',
    author_email='',
    description=''
)
