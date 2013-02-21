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

"""
The __init__.py file for PyFarm which handles module backports, adding the
project root as a site package, as well as setup of several top level
variables.
"""

import os
import pkg_resources
from os.path import join, abspath, dirname, isdir

# setup version information
__version__ = (0, 4, 0)
__versionstr__ = ".".join(map(str, __version__))

PYTHON_ROOT = dirname(abspath(__file__))

# construct the distribution object
try:
    # If the setup.py is running then we need to be sure
    # we don't use the installed package to construct
    # the dist object.
    if os.environ.get('PYFARM_SETUP_RUNNING') == 'True':
        raise EnvironmentError("setup.py is running")

    dist = pkg_resources.get_distribution('pyfarm==%s' % __versionstr__)

except (TypeError, EnvironmentError, pkg_resources.DistributionNotFound):
    dist = pkg_resources.Distribution(
        location=abspath(join(PYTHON_ROOT, '..', '..', '..')),
        project_name='pyfarm',
        version=__versionstr__
    )

# depending on what setup.py operation was called (install/develop) we
# need to construct a few possible paths
_etc_paths_ = filter(
    lambda path: isdir(path) and path.endswith('etc'),
    (
        join(dist.location, 'etc'),                         # any other mode
        abspath(join(PYTHON_ROOT, '..', '..', '..', 'etc')) # develop mode
    )
)

# be sure we were able to construct a least one etc path
if not _etc_paths_:
    raise OSError("failed to construct any etc paths")

PYFARM_ETC = os.environ.get('PYFARM_ETC') or _etc_paths_[0]
