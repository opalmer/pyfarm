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
import fnmatch
import logging

from pyfarm import logger
from pyfarm.preferences import prefs

from twisted.python import log

PATHS = []

def paths():
    '''
    returns the paths we should be searching in when looking for
    jobtype modules
    '''
    # should only run the search once
    if PATHS:
        return PATHS

    log.msg("searching for jobtype paths as defined in preferences and $PYFARM_JOBTYPES")

    def addpath(path):
        if path in PATHS: return
        elif not path: return
        elif not os.path.isdir(path):
            log.msg(
                "%s is not a directory, skipping for jobtype search" % path,
                level=logging.WARNING
            )
        else:
            PATHS.append(path)
    # end addpath

    # iterate over paths both preferences and the PYFARM_JOBTYPES
    # environment variable and run each result through addpath
    map(addpath, prefs.get('jobtypes.search-paths'))
    map(addpath, os.environ.get('PYFARM_JOBTYPES', '').split(os.pathsep))

    return PATHS
# end paths
#
