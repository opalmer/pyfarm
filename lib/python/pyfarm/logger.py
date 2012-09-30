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
import sys
import time
import logging
import fnmatch
from logging import handlers

from pyfarm.preferences import prefs

import colorama
from twisted.python import log
from colorama import Fore, Back, Style

log.FileLogObserver.timeFormat = prefs.get('logging.timestamp')
colorama.init()

# dictionary of log levels and their associated
# styles and colors
ENABLE_TERMCOLOR = prefs.get('logging.termcolor')
TERMCOLOR = {
    logging.DEBUG : (Style.DIM, Style.RESET_ALL),
    logging.INFO : (Fore.GREEN, Fore.RESET),
    logging.WARNING : (Fore.YELLOW, Fore.RESET),
    logging.ERROR : (Fore.RED, Fore.RESET),
    logging.CRITICAL : (
        Back.RED+Fore.WHITE+Style.BRIGHT, Back.RESET+Fore.RESET+Style.RESET_ALL
    )
}

class Observer(log.FileLogObserver):
    '''
    Logging observer for streams

    :param None or string or file stream:
        The path or stream to log to.  If the argument provided
        is a string then we will attempt to create a log file
        at the given location.  In the event the provided 'stream'
        is actually None then send everything out via print.

    :param string mode:
        the mode to open stream in (only applies to file streams)

    :param integer backups:
        number of backups to keep of logfiles

    :param string or list observe:
        If provided then this observer will only listen to a
        specific system.  This can be extremely useful when
        we only want to listen to output from a specific source
        to a specific location.

        >>> from twisted.python import log
        >>> observer = LogObserver('/tmp/test.log', observe='ProcessLog')
        >>> observer.start()
        >>> log.msg("test", system="PorcessLog") # sent to /tmp/test.log

        If observe is a list then any system which appears in the observe
        list will be displayed or output.

    :param string format:
        key to use to lookup the logger format from prefs.get('logging.formats')

    :param integer level:
        Sets the log level for this specific observer.  If no level is provided
        then logging.level will be used from the preferences

    :exception TypeError:
        raised if the provided or resolved stream argument is
        not a file stream

    :exception KeyError:
        raised if the provided format is not defined
    '''
    STREAMS = {}
    FORMATS = prefs.get('logging.formats')
    BACKUPS = prefs.get('logging.backups')
    LEVEL = prefs.get('logging.level')
    
    def __init__(self, stream=sys.stderr, format='default'):
        self.level = self.LEVEL
        self.custom_levels = prefs.get('logging.custom-levels')
        self.format = self.FORMATS[format]

        # resolve the full path to the file we intend to write to
        if isinstance(stream, (str, unicode)):
            stream = os.path.abspath(os.path.expandvars(stream))

        # if we are provided a string and it's not something that
        # we're already logging to then create the required directories,
        # rollover existing files and start writing
        if isinstance(stream, (str, unicode)) and stream not in self.STREAMS:
            dirname = os.path.dirname(stream)

            # if the directory does not exist create it
            if not os.path.isdir(dirname):
                os.makedirs(dirname)

            # if the file already exists and the mode
            # is append then rollover the file on disk
            if os.path.isfile(stream) and self.BACKUPS:
                rotate = handlers.RotatingFileHandler(stream, backupCount=self.BACKUPS)
                rotate.doRollover()

            stream = open(stream, 'w')

        elif stream in self.STREAMS:
            stream = self.STREAMS[stream]

        self.stream = stream
        if stream is not None:
            if not isinstance(stream, file):
                raise TypeError("provided argument is not a file stream or ")

            self.name = self.stream.name
            log.FileLogObserver.__init__(self, stream)
        else:
            self.name = 'print()'
    # end __init__

    def __colorize(self, eventDict, msg):
        '''
        given information from the event dictionary and a message
        output a properly colorized message
        '''
        style_start = ""
        style_end = ""

        if ENABLE_TERMCOLOR:
            level = eventDict.get('level', logging.DEBUG)
            style_start, style_end = TERMCOLOR.get(level, ('', ''))

        return style_start + msg + style_end
    # end __colorize

    def __call__(self, eventDict):
        msg = self.emit(eventDict)
    # end __call__

    def emit(self, eventDict, stdout=True):
        '''
        Takes an incoming event dictionary and determines if we should
        be logging its data and to what format.
        '''
        text = log.textFromEventDict(eventDict)

        if text is None:
            return

        # scrub __main__. from the start of system names
        eventDict['system'] = eventDict['system'].replace("__main__.", "")

        # systems of HTTPChannel should really be xmlrpc in our
        # case
        if eventDict['system'].startswith("HTTPChannel"):
            eventDict['system'] = 'XML-RPC'

        # retrieve the logging string
        level = eventDict.get('level') or logging.DEBUG
        integer = isinstance(level, int)

        # determine if we should even emit the given log level
        if integer and level < self.level:
            return

        elif isinstance(level, str):
            level = level.upper()
            if level in self.custom_levels and self.custom_levels.get(level) < self.level:
                return

        # if level is an integer then we need to either
        # retrieve the name or construct one
        if integer and level in logging._levelNames:
            level = logging.getLevelName(level)

        elif integer:
            level = prefs.get('logging.unknown-level') % level

        timeStr = self.formatTime(eventDict['time'])
        fmtDict = {
            'system': eventDict['system'],
            'text': text.replace("\n", "\n\t"),
            'level' : level.upper()
        }

        msg = timeStr + " " + self.format % fmtDict

        if self.stream is not None:
            log.util.untilConcludes(self.write, msg+"\n")
            log.util.untilConcludes(self.flush)

        else:
            msg = self.__colorize(eventDict, msg)
            msg = msg.strip()
            if stdout:
                print msg
            return msg
    # end emit
# end Observer



class LoggingBaseClass(object):
    '''Adds a custom self.log method to an inherited class'''
    def log(self, msg, **kwargs):
        # adds the namespace of the class to the keyword
        # system argument
        if 'system' not in kwargs:
            kwargs['system'] = self.__module__ + "." + self.__class__.__name__

        log.msg(msg, **kwargs)
    # end log
# end LoggingBaseClass


def timestamp():
    '''
    returns a preformatted timestamp based on either an
    argument or preference
    '''
    format = prefs.get('logging.timestamp')
    return time.strftime(format)
# end timestamp
