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

'''
The __init__.py file for PyFarm which handles module backports, adding the
project root as a site package, as well as setup of several top level
variables.
'''

import os
import sys
import pkg_resources
from os.path import join, abspath, dirname, isdir

# setup version information
__version__ = (0, 4, 0)
__versionstr__ = ".".join(map(str, __version__))

# construct the distribution object
try:
    dist = pkg_resources.get_distribution('pyfarm==%s' % __versionstr__)

except (TypeError, pkg_resources.DistributionNotFound):
    dist = pkg_resources.Distribution(
        location=abspath(join(dirname(abspath(__file__)), '..', '..', '..')),
        project_name='pyfarm',
        version=__versionstr__
    )

PYFARM_ETC = os.environ.get('PYFARM_ETC') or join(dist.location, 'etc')

if not isdir(PYFARM_ETC):
    raise OSError("etc directory does not exist: %s" % PYFARM_ETC)