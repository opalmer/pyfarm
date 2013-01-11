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
import site

__version__ = (0, 4, 0)

PYFARM_PACKAGE = os.path.dirname(os.path.abspath(__file__))
PYFARM_ROOT = os.path.abspath(os.path.join(PYFARM_PACKAGE, "..", "..", ".."))
PYFARM_PYTHONROOT = os.path.join(PYFARM_ROOT, "lib", "python")
PYFARM_ETC = os.environ.get('PYFARM_ETC') or \
             os.path.join(PYFARM_ROOT, "etc")

site.addsitedir(PYFARM_PYTHONROOT)

# Attempt to import a few functions that may or
# may not exist depending on the python version.
# For those that do not create the missing objects.

# collections.OrderedDict
try:
    from collections import OrderedDict
except ImportError:
    import collections
    from pyfarm.datatypes.backports import OrderedDict
    collections.OrderedDict = OrderedDict

# collections.namedtuple
try:
    from collections import namedtuple
except ImportError:
    import collections
    from pyfarm.datatypes.backports import namedtuple
    collections.namedtuple = namedtuple

# itertools.product
try:
    from itertools import product
except ImportError:
    import itertools
    from pyfarm.datatypes.backports import product
    itertools.product = product

# itertools.permutations
try:
    from itertools import permutations
except ImportError:
    import itertools
    from pyfarm.datatypes.backports import permutations
    itertools.permutations = permutations

# Python < 2.6 is missing the delete keyword
if sys.version_info[0:2] <= (2, 6):
    import tempfile
    from pyfarm.datatypes.backports import NamedTemporaryFile
    tempfile.NamedTemporaryFile = NamedTemporaryFile
