# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
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
    if os.environ.get('PYFARM_SETUP_IGNORE_REQUIREMENTS') == 'True':
        return requires

    # split out versioned/unversioned requirements for easier
    # maintenance
    unversioned_requires = [
        'zope.interface', 'nose', 'appdirs', 'PyYaml',
        'colorama'
    ]
    versioned_requires = [
        'python>=2.6.0', 'twisted>=11.0.0', 'psutil>=0.6.0',
        'netifaces>=0.8', 'sqlalchemy>=0.7.0'
    ]

    # windows specific requirements
    if sys.platform.startswith("win"):
        unversioned_requires.append("pywin32")

    # add a couple of additional requirements for Python2.6
    if PY_MINOR == 6:
        extra = ['ordereddict', 'argparse']
        unversioned_requires.extend(extra)

    requires.extend(versioned_requires)
    requires.extend(unversioned_requires)

    # allow this check to be skipped in some cases in case
    if os.environ.get('PYFARM_SETUP_IGNORE_PYQT_CHECK') != 'True':
        # we have to manually validate the PyQt requirements since
        # setuptools cannot seems to reliability download/install/validate
        # pyqt
        try:
            from PyQt4 import QtCore

            try:
                major, minor, micro = map(int, QtCore.PYQT_VERSION_STR.split("."))

                if major < 4:
                    raise DistutilsError("PyQt4 version < 4.0.0")

                elif minor < 5:
                    raise DistutilsError("PyQt4 version < 4.5.0")

            except ValueError:
                raise DistutilsError("failed to parse version for PyQt")


        except ImportError:
            raise DistutilsError("failed to import PyQt4")

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
