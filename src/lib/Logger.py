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
'''

import logging
import logging.config

__MODULE__ = "lib.Logger"

# the lower the level, the more dangerous it is!
LEVELS = {
    'CRITICAL': int(logging.CRITICAL),  # lvl 50
    'ERROR': int(logging.ERROR), # lvl 40
    'WARNING': int(logging.WARNING), # lvl 30
    'UI' : 23,
    'NETWORK' : 22,
    'SETTINGS' : 21,
    'INFO': int(logging.INFO), # lvl 20
    'DEBUG.UI' : 13,
    'DEBUG.NETWORK' :  12,
    'DEBUG.SETTINGS'  : 11,
    'DEBUG': int(logging.DEBUG), # lvl 10
    'SQLITE' : 5,
    'DEBUG.NETCOM' : 4,
}

def SetupLog(cfg="cfg/logging.ini"):
    '''
    Setup the main logging object, run getLogger() when ready to
    create and use the logging object.

    VARIABLES:
    level (str) -- max level to return log info for
    module (str) -- module name log for
    '''
    # see http://docs.python.org/library/logging.html#logging.LogRecord
    # for creating your own log records
    # see http://docs.python.org/library/logging.html#configuration-file-format
    # for configuration file format
    logging.config.fileConfig(cfg)

    # add some extra levels
    #  you must access them with log.log(lvl, msg)
    for name, level in LEVELS.items():
        logging.addLevelName(level, name)

    return logging

# basic example usage of new logging facility
if __name__ == "__main__":
    import os
    import sys

    log = SetupLog(os.path.basename(sys.argv[0]))
    log.info("Current log level is %i" % log.getEffectiveLevel())
    log.info("This is a log message")
    log.critical("Fail!")
    log.debug("This is a program, I am sure of it")

    # custom log levels
    log.log(11, "Now setting a parameter")
    log.log(21, "The parameter is")
    log.log(12, "Now connecting to")
    log.log(22, "Connected to")

    if log.getEffectiveLevel() > EXTENDED_LEVELS["SQLITE"]:
        log.critical("Your log level is not low level enough to handle calls from SQLITE")
        log.info("Changing log level to %i" % EXTENDED_LEVELS["SQLITE"])
        log.setLevel(EXTENDED_LEVELS["SQLITE"])
        log.info("New log level set!")

    # now try using low level logging
    log.debug("Log level: %i" % EXTENDED_LEVELS["SQLITE"])
    log.log(5, "Sqlite operation")
