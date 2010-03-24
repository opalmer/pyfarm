'''
HOMEPAGE: www.pyfarm.net
INITIAL: March 22 2010
PURPOSE: To provide a standard logging facility for PyFarm

This file is part of PyFarm.
Copyright (C) 2008-2010 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.


====================
DEV NOTES:
+ DOCS: http://docs.python.org/library/logging.html
+ Perhaps add custom levels to access with log.this('line')
such methods could be used for custom errors, process log
seperation, etc. (or perhaps something like log.all())
+ Add option for individual logs or one complete logger
+ Provide better formatting for log time
+ Possible replacement for the current inter-node logging system
        ----This may not be a good idea as this logging system is
        meant more for the main thread of execution (Main.pyw)
+ Option to read config from file (and/or set via keyword arguments)
+ A new log record function/class to get last log/etc
+ Buffer handeling to better cope with something like log.all()
+ log shutdown handeling
+ in appplication logging disable with logging.disable()
    This will require an array to handle the main logging objects
====================
'''

import logging


class LogLevelException(Exception):
    '''
    Raised when an invalid level is presented to the
    logger by the user
    '''
    def __init__(self, level):
        self.level = level

    def __str__(self):
        return repr("%s is not a valid log level" % self.level)


def LogSetup(module='Default', level='info', log='PyFarm.log'):
    '''
    Setup logging and return the main logging object

    VARIABLES:
        module (str) -- Module to create the logger for
        level (str) -- Maximum level to log for the configured
        object
        log (str) -- Log to write all infomation to
    '''
    levels = {
        'debug': logging.DEBUG,
        'info': logging.INFO,
        'warning': logging.WARNING,
        'error': logging.ERROR,
        'critical': logging.CRITICAL
    }

    if level.lower() not in levels.keys():
        raise LogLevelException(level)
    else:
        lvl = levels[level]

        # create a logger and stream handeler
        logger = logging.getLogger(module)
        streamHandeler = logging.StreamHandeler()

        # set the log level for both logging objects
        logger.setLevel(lvl)
        streamHandeler.setLevel(lvl)

        # configure log formatting and apply it
        formatting = logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
        streamHandeler.setFormatter(formatting)
        logger.addHandeler(streamHandeler)


if __name__ == "__main__":
    print True