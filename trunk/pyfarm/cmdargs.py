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
# You should have received a copy of the GNU Lesser General Public Lic

'''
Provides a common set of command line options and arguments.  In future versions
this may also serve as a common interface to argparse
'''

import argparse

parser = argparse.ArgumentParser()
parser.add_argument(
    '--force-kill', action='store_true', default=False,
        help='kill any currently running process before starting'
)
parser.add_argument(
    '--wait', action='store_true', default=False,
    help='waits for running processes to terminate first'
)
parser.add_argument(
    '--log', default=None,
    help='location to send the logfile to'
)
parser.add_argument(
    '--remove-lock', action='store_true', default=False,
    help='Removes the lock file on disk before starting if one exists.  This' +
         ' is mainly used if you already know the process does not exist and' +
         ' you do not wish to remove the lock file manually.'
)
