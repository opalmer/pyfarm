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

from pyfarm.datatypes.enums import DEFAULT_JOBTYPES, DEFAULT_SOFTWARE
from pyfarm.datatypes.system import TOTAL_RAM, CPU_COUNT
from pyfarm.cmdargs import *

parser.description = "Entry point for PyFarm's client."

parser.add_argument(
    '--jobtypes', default=DEFAULT_JOBTYPES,
    help='comma separated list of jobtypes to include or exclude'
)
parser.add_argument(
    '--ram', default=TOTAL_RAM, type=int,
    help='sets the total amount of ram available to the farm (in MB)'
)
parser.add_argument(
    '--cpus', default=CPU_COUNT, type=int,
    help='sets the total number of cpus available to the farm'
)
parser.add_argument(
    '--groups', default='*', type=tolist,
    help='comma separated list of groups those host belongs to'
)
parser.add_argument(
    '--software', default=DEFAULT_SOFTWARE, type=tolist,
    help='comma separated list of software the host can run'
)
parser.add_argument(
    '--master', default=None, type=evalnone,
    help='sets the master for the current session and in the database'
)
parser.add_argument(
    '--store-master', default=False, action='store_true',
    help='if True then set the master in the database to the provided --master input'
)
parser.add_argument(
    '--online', default=True, type=tobool,
    help='if True then mark of those as online in the database'
)
parser.add_argument(
    '--verify-master', default=False, action='store_true',
    help='if provided then check to make sure we can ping current master'
)
