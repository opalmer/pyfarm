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
# You should have received a copy of the GNU Lesser General Public Lic

from pyfarm.preferences import prefs
from pyfarm.cmdargs import *
from pyfarm.net.functions import openport

parser.description = "Entry point for PyFarm's master."

try:
    port = prefs.get('network.ports.master')

except KeyError:
    port = openport()

parser.set_defaults(port=port)

parser.add_argument(
    '--queue', default=True, type=tobool,
    help='enables or disables queue events'
)
parser.add_argument(
    '--assignment', default=True, type=tobool,
    help='enables or disables the assignment queue'
)
