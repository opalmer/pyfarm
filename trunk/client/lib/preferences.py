# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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
import ConfigParser
import multiprocessing

ETC = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "etc"))
CONFIG = os.path.join(ETC, "client.ini")
CPU_COUNT = multiprocessing.cpu_count()

# ensure the preference file exists
if not os.path.isfile(CONFIG):
    raise IOError("missing client configuration %s" % CONFIG)

# read configuration
cfg = ConfigParser.ConfigParser()
cfg.read(CONFIG)

# establish global preferences
PRINT_OUTPUT = cfg.getboolean('LOGGING', 'print')
PORT = cfg.getint('NETWORK', 'port')
MAX_JOBS = int(eval(cfg.get('PROCESSING', 'max_jobs')))
PATHS = cfg.get('PROCESSING', 'program_paths').split(',')

if __name__ == '__main__':
    print port()