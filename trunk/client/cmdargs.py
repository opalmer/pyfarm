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

import types
import psutil

import common.datatypes
from common import datatypes
from common.cmdargs import parser

parser.add_option(
    '--limit-jobtypes', default='*',
    help='comma separated list of jobtypes to include or exclude'
)
parser.add_option(
    '--host-ram', default=None,
    help='sets the total amount of ram available to the farm (in MB)'
)
parser.add_option(
    '--host-cpus', default=None,
    help='sets the total number of cpus available to the farm'
)
parser.add_option(
    '--host-groups', default='',
    help='comma separated list of groups thos host belongs to'
)

def processOptions(options):
    '''sets up constants based on input provided by optparse'''
    __setHostRAM(options.host_ram)
    __setHostCPU(options.host_cpus)
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
        '--host-ram'
    )

    if isinstance(value, types.IntType):
        psutil.TOTAL_PHYMEM = value * 1024 * 1024
# end __setHostRAM

def __setHostCPU(option):
    '''sets the host cpu count if the value provided to option is valid'''
    value = __validNumber(
        option,
        1, psutil.NUM_CPUS,
        '--host-cpus'
    )

    if isinstance(value, types.IntType):
        psutil.NUM_CPUS = value
# end __setHostCPU
