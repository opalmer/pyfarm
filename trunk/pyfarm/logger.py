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
import time
import inspect
import logging
import fnmatch
import colorama
from colorama import Fore, Back, Style
from logging import handlers

from pyfarm.preferences import prefs

from twisted.python import log
from pyfarm import datatypes

log.FileLogObserver.timeFormat = prefs.get('logging.timestamp')
colorama.init()

# lists of currently instanced stream and observers
STREAMS = {}
OBSERVERS = []

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
    Logging observer for sys.stdout and sys.stderr

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
    def __init__(self, stream, mode='a',
                 backups=prefs.get('logging.backups'), observe=None,
                 format='default', level=None):
        self.observe = observe
        self.format = format
        self.formats = prefs.get('logging.formats')
        self.level = level or prefs.get('logging.level')
        self.custom_levels = prefs.get('logging.custom-levels')

        self.__running = False

        # ensure the requested format exists before we setup the logger
        if format not in self.formats:
            raise KeyError("no such format '%s' exists")
        else:
            self.format = self.formats.get(format)

        # if we are provided a string then prepare and
        # attempt to log the the provided location
        if isinstance(stream, str):
            path = os.path.abspath(stream)
            dirname = os.path.dirname(path)

            # if the directory does not exist create it
            if not os.path.isdir(dirname):
                os.makedirs(dirname)

            # if the file already exists and the mode
            # is append then rollover the file on disk
            if os.path.isfile(path) and mode == 'a':
                rotate = handlers.RotatingFileHandler(path, backupCount=backups)
                rotate.doRollover()

            stream = open(path, mode)

        # attributes for external use
        self.stream = stream
        if stream is not None:
            if not isinstance(stream, file):
                raise TypeError("provided argument is not a file stream or ")

            self.name = self.stream.name
            log.FileLogObserver.__init__(self, stream)

        else:
            self.name = 'print()'
    # end __init__

    def __isstream(self):
        '''returns true if we are logging to a file stream'''
        return isinstance(self.stream, file)
    # end __isstream

    def __log(self, msg, level=logging.DEBUG):
        '''used for internal calls to log'''
        log.msg(msg, system='logger.Observer', level=level, observe=True)
    # end __log

    def __observable(self, eventDict):
        '''
        Returns True if we should be observing the given system based on the
        value(s) in self.observe or if eventDict['observe'] is True
        '''
        # only filter if filtering is enabled
        if prefs.get('logging.enable-filtering'):
            for filter in prefs.get('logging.filter-systems'):
                if not fnmatch.fnmatchcase(eventDict['system'], filter):
                    continue

                for pattern in prefs.get('logging.exclude-message-filter'):
                    for message in eventDict['message']:
                        if fnmatch.fnmatchcase(message, pattern):
                            return False

        # see if the we have explicitly silenced the system
        silenced = prefs.get('logging.silence-systems')
        if silenced:
            if eventDict['system'] in silenced:
                return False

        if isinstance(self.observe, str):
            return eventDict['system'] == self.observe

        elif isinstance(self.observe, datatypes.LIST_TYPES):
            return eventDict['system'] in self.observe

        return True
    # end __observable

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

    def emit(self, eventDict):
        '''
        Takes an incoming event dictionary and determines if we should
        be logging its data and to what format.
        '''
        text = log.textFromEventDict(eventDict)

        if text is None:
            return

        # scrub __main__. from the start of system names
        eventDict['system'] = eventDict['system'].replace("__main__.", "")

        # if we are observing a specific system and the
        # emitted system does not match that system
        # then skip the rest of the function
        if not self.__observable(eventDict):
            return

        # systems of HTTPChannel should really be xmlrpc in our
        # case
        if eventDict['system'].startswith("HTTPChannel"):
            eventDict['system'] = 'XML-RPC'

        # retrieve the logging string
        level = eventDict.get('level') or\
                eventDict.get('logLevel') or \
                logging.DEBUG
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

        msg = timeStr + " " + log._safeFormat(self.format, fmtDict)

        if self.stream is not None:
            log.util.untilConcludes(self.write, msg+"\n")
            log.util.untilConcludes(self.flush)

        else:
            msg = self.__colorize(eventDict, msg)
            print msg.strip()
    # end emit

    def start(self):
        '''
        Start observing log events and produce warnings if we have started
        logging to a file to which we are already logging.
        '''
        if self.__running:
            self.__log("%s is already running" % self)
            return

        log.addObserver(self.emit)
        self.__running = True

        # add self to observers
        OBSERVERS.append(self)

        # tell us where we are logging and if
        # we are observing a specific system
        msg = "logging to %s" % self.name
        if self.observe is not None:
            observing = self.observe
            if isinstance(observing, str):
                observing = [observing]
            msg += " (observing: %s)" % ", ".join(observing)

        self.__log(msg, level=logging.INFO)

        # if the current stream name is not in STREAM we need
        # add a new entry
        if self.__isstream() and self.name not in STREAMS:
            STREAMS[self.name] = 1

        # warn about stream reuse
        elif self.__isstream() and STREAMS.get(self.name) > 1:
            msg = "already logging to %s, this could produce " % self.name
            msg += "unexpected results"
            self.__log(msg, level=logging.WARNING)
            STREAMS[self.name] += 1
    # end start

    def stop(self):
        '''
        Stop observing log events and remove any necessary entries from
        the STREAMS global
        '''
        if not self.__running:
            self.__log("%s is not running" % self)
            return

        log.removeObserver(self.emit)
        self.__running = False
        self.__log("stopped logging to %s" % self.name)
        OBSERVERS.remove(self)

        if self.__isstream() and STREAMS.get(self.name) == 1:
            del STREAMS[self.name]

        elif self.__isstream() and STREAMS.get(self.name) > 1:
            STREAMS[self.name] -= 1
    # end stop
# end Observer


class LoggingBaseClass(object):
    '''Adds a custom self.log method to an inherited class'''
    def log(self, msg, **kwargs):
        module = inspect.getmodule(self.__class__).__name__
        classname = self.__module__ + "." + self.__class__.__name__
        kwargs['system'] = "%s.%s" % (module, classname)
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

# setup a default observer
observer = Observer(None)
observer.start()
