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
import site
import sqlalchemy.util

PYFARM_PACKAGE = os.path.dirname(os.path.abspath(__file__))
PYFARM_ROOT = os.path.abspath(os.path.join(PYFARM_PACKAGE, "..", "..", ".."))
PYFARM_PYTHONROOT = os.path.join(PYFARM_ROOT, "lib", "python")
PYFARM_ETC = os.environ.get('PYFARM_ETC') or \
             os.path.join(PYFARM_ROOT, "etc")
PYFARM_JOBTYPES = os.environ.get('PYFARM_JOBTYPES') or \
                  os.path.join(PYFARM_PYTHONROOT, "pyfarm", "jobtypes")

site.addsitedir(PYFARM_PYTHONROOT)

# override the default sqlalchemy named tuple
# with a slightly easier to read version
from pyfarm.utility import NamedTupleRow
sqlalchemy.util.NamedTuple = NamedTupleRow
