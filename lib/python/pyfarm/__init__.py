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

"""
The __init__.py file for PyFarm which handles module backports, adding the
project root as a site package, as well as setup of several top level
variables.
"""

import os
import pkg_resources
from os.path import join, abspath, dirname, isdir

# setup version information
__version__ = (1, 0, 0)
__versionstr__ = ".".join(map(str, __version__))
__author__ = "Oliver Palmer"

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
    raise OSError("FIXME: failed to construct any etc paths")

PYFARM_ETC = os.environ.get('PYFARM_ETC') or _etc_paths_[0]
