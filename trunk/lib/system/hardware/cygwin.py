'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 29 2011
PURPOSE: To query and return information about the local system (cygwin)

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

# placeholders
def cpuCount(): return 0
def cpuSpeed(): return 0
def ramTotal(): return 0
def ramAvailable(): return 0
def virtualMemoryTotal(): return 0
def virtualMemoryAvailable(): return 0
def load(): return 0, 0, 0
def uptime(): return 0
def osName(): return os.path.basename(__file__).split('.')[0]
def osVersion(): return 0
def architecture(): return 0
