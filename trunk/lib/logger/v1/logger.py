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
import pprint

import level
import config
import format

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
        self.config = config.ReadConfig()

        # transfer the global settings to the Logger object
        Logger.ENABLE = True
        Logger.LEVEL = self.config.globals.LEVEL
        Logger.ALLOW_TRACE = self.config.globals.ALLOW_TRACE
        Logger.WARN_MISSING_LEVEL = self.config.globals.WARN_MISSING_LEVEL
        Logger.DEFAULT_STREAM = self.config.globals.DEFAULT_STREAM

        # prepare the config dictionary and add any missing keys
        for levelConfig in self.config.levels:
            config.build(levelConfig)

            # build the level
            level.Base(levelConfig)


            # TODO: Construct level object here
            # TODO: Level object should be passed formatting information
            print "See TODOs in logger.py"
            print "-importing logging should setup the logger (logging.log('hi!')"
            print "-concept of handlers/streams needs better definition"
            setattr(self, levelConfig['function'], levelConfig)

    def __getattr__(self, level):
        '''Get an attribute from the logger object'''
        try:
            if not Logger.ENABLE:
                return

            data = object.__getattribute__(self, level)
            level = level.Base(data)


        except AttributeError:
            if Logger.WARN_MISSING_LEVEL:
                print "WARNING: No such log level %s" % attr


if __name__ == '__main__':
    logger = Logger()