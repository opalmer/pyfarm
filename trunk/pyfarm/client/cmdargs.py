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

import psutil

from pyfarm import datatypes
from pyfarm.cmdargs import parser

parser.add_option(
    '--jobtypes', default=datatypes.DEFAULT_JOBTYPES,
    help='comma separated list of jobtypes to include or exclude'
)
parser.add_option(
    '--ram', default=None,
    help='sets the total amount of ram available to the farm (in MB)'
)
parser.add_option(
    '--cpus', default=None,
    help='sets the total number of cpus available to the farm'
)
parser.add_option(
    '--groups', default='*',
    help='comma separated list of groups those host belongs to'
)
parser.add_option(
    '--software', default=datatypes.DEFAULT_SOFTWARE,
    help='comma separated list of software the host can run'
)
parser.add_option(
    '--master', default=None,
    help='sets the master for the current session and in the database'
)

def processOptions(options):
    '''sets up constants based on input provided by optparse'''
    __setHostRAM(options.ram)
    __setHostCPU(options.cpus)

    # preprocess the software arguments
    software = []
    if isinstance(options.software, (str, unicode)):
        for value in options.software.split(","):
            v = value.strip()
            if v not in software:
                software.append(v)

    # preprocess the jobtype arguments
    jobtypes = []
    if isinstance(options.jobtypes, (str, unicode)):
        for value in options.jobtypes.split(","):
            v = value.strip()
            if v not in jobtypes:
                jobtypes.append(v)

    options.software = software
    options.jobtypes = jobtypes
# end processOptions

def __validNumber(value, min, max, name):
    '''
    checks the range of a given value and raise a ValueError if
    it fails
    '''
    if value is None:
        return False

    try:
        value = int(value)

    except ValueError:
        raise ValueError('cannot convert %s to an integer')

    if value > max:
        raise ValueError("%s cannot be greater than %s" % (name, max))

    elif value < min:
        raise ValueError("%s cannot be less than %s" % (name, min))

    return value
# end __validNumber

def __setHostRAM(option):
    '''sets the host ram amount if the value provided to option is valid'''

    value = __validNumber(
        option, 1, psutil.TOTAL_PHYMEM / 1024 / 1024,
        '--ram'
    )

    if isinstance(value, int) and not value in datatypes.BOOLEAN_TYPES:
        psutil.TOTAL_PHYMEM = value * 1024 * 1024
# end __setHostRAM

def __setHostCPU(option):
    '''sets the host cpu count if the value provided to option is valid'''
    value = __validNumber(
        option,
        1, psutil.NUM_CPUS,
        '--cpus'
    )

    if isinstance(value, int) and value not in datatypes.BOOLEAN_TYPES:
        psutil.NUM_CPUS = value
# end __setHostCPU
