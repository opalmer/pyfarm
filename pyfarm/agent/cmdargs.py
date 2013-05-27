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

from pyfarm.config.core import Loader
from pyfarm.datatypes.system import TOTAL_RAM, CPU_COUNT
from pyfarm.cmdargs import *
from pyfarm.net import getPort

parser.description = "Entry point for PyFarm's host."

netprefs = Loader("network.yml")

DEFAULT_JOBTYPES = []  # TODO: replace with object
DEFAULT_SOFTWARE = []  # TODO: replace with object

try:
    port = netprefs.get('ports.host')

except KeyError:
    port = getPort()

parser.set_defaults(port=port)

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
