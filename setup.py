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
from distutils.core import setup

# ensure pyfarm itself can be imported and if
# not add it to the path
try:
    import pyfarm

except ImportError:
    cwd = os.path.dirname(os.path.abspath(__file__))
    root = os.path.join(cwd, 'lib', 'python')

    if root not in sys.path:
        sys.path.insert(0, root)

from pyfarm import __versionstr__

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
        'colorama'
    ]

    if os.environ.get('READTHEDOCS', None) != 'True':
        unversioned_requires.append('PyYaml')

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

    # add a couple of additional requirements for Python2.6
    if PY_MINOR == 6:

        unversioned_requires.extend(['ordereddict', 'argparse'])

    requires.extend(versioned_requires)
    requires.extend(unversioned_requires)

    return requires
# end requirements

libdir = os.path.join('lib', 'python')
requires = requirements()

setup(
    name='pyfarm',
    version=__versionstr__,
    package_dir={'' : libdir},
    packages=setuptools.find_packages(libdir),
    setup_requires=requires,
    install_requires=requires,
    url='http://pyfarm.net',
    license='LGPL',
    author='Oliver Palmer',
    author_email='',
    description='',
    scripts=setuptools.findall('bin')
)
