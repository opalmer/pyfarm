# No shebang line, this module is meant to be imported
#
# INITIAL: June 19 2011
# PURPOSE: To create an operate the logger
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

import sys

from config import ReadConfig

class Logger(object):
    '''
    Read the logger configration and create an object to handle calls
    to individual log levels.
    '''
    # 'global' settings for all Logger instances.  These settings are typically
    # controlled by etc/globals.ini
    LEVEL = 0
    ALLOW_TRACE = False
    WARN_MISSING_LEVEL = True
    DEFAULT_STREAM = sys.stdout

    def __init__(self):
        self.config = ReadConfig()
        print type(self.config.globals.LEVEL)


        for level in self.config.levels:
            print level

    def __getattr__(self, level):
        '''Get an attribute from the logger object'''
        try:
            log = object.__getattribute__(self, level)

        except AttributeError:
            if Logger.WARN_MISSING_LEVEL:
                print "WARNING: No such log level %s" % attr


if __name__ == '__main__':
    logger = Logger()
    print Logger.WARN_MISSING_LEVEL
    #logger.test