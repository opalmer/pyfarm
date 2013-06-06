# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

"""basic module which handles and controls logging for pyfarm"""

import os
import sys
import inspect
import logging
from logging.handlers import  RotatingFileHandler
from twisted.python import log
from colorama import Fore, Back, Style, init
init()

log.FileLogObserver.timeFormat = '%m-%d-%Y %H:%M:%S'

# add a verbose level
logging.addLevelName(15, 'VERBOSE')
logging.VERBOSE = 15

logger = None

BACKUP_COUNT = True
ENABLE_TERMCOLOR = True
TERMCOLOR = {
    logging.DEBUG : (Style.DIM, Style.RESET_ALL),
    logging.INFO : (Fore.GREEN, Fore.RESET),
    logging.WARNING : (Fore.YELLOW, Fore.RESET),
    logging.ERROR : (Fore.RED, Fore.RESET),
    logging.CRITICAL : (
        Back.RED+Fore.WHITE+Style.BRIGHT, Back.RESET+Fore.RESET+Style.RESET_ALL
       )
}

class Logger(log.LogPublisher):
    """
    Logger class which should be used in place of the logger provided
    by twisted.python.log.

    :param system:
        if provided value is a string use it for the logger name
        otherwise try to determine the module and classname of the
        object

    :param boolean stdout_observer:
        autocreates an observer for sys.stdout

    :param boolean inherit_observers:
        if true then inherit observers from the base pyfarm.logger.logger
        instance
    """
    def __init__(self, system=None, stdout_observer=True, inherit_observers=True):
        log.LogPublisher.__init__(self)
        self.stdout_observer = stdout_observer

        if inherit_observers and logger is not None:
            for observer in logger.observers:
                self.addObserver(observer)

        if system is None:
            system = 'pyfarm'

        elif not isinstance(system, (str, unicode)) and system is not None:
            module = inspect.getmodule(system)
            classname = system.__class__.__name__

            if module.__name__ != '__main__':
                system = module.__name__ + "." + classname
            else:
                system = classname

        self.system = system.replace("pyfarm.", "") # don't need the full name
        self.disabled = 0
        self.level = logging.DEBUG
        self.__observers = []

        if stdout_observer:
            self.startLogger()
    # end __init__

    def startLogger(self):
        """
        starts the logger by either adding a default observer or
        restoring any previous observers
        """
        if self.__observers:
            self.observers = self.__observers[:]
            del self.__observers[:]

        elif not self.observers and self.stdout_observer:
            self.addObserver()
    # end start

    def stop(self):
        """stops the logger and removes all observers"""
        self.__observers = self.observers[:]
        del self.observers[:]
    # end stop

    def addObserver(self, other=None):
        """adds the observer if provided or an observer for sys.stdout"""
        if other is None:
            other = Observer()
        log.LogPublisher.addObserver(self, other)
    # end addObserver

    def setLevel(self, level):
        """sets the max level this logger will emit"""
        if isinstance(level, int):
            level = logging.getLevelName(level)

        logger.debug("setting level for %s to %s" % (self.system, level))
        self.level = level
    # end addLogger

    def msg(self, *message, **kw):
        if self.disabled or kw['level'] < self.level:
            return

        kw.setdefault('system', self.system)
        log.LogPublisher.msg(self, *message, **kw)
    # end msg

    def debug(self, *args, **kwargs):
        kwargs.setdefault('level', logging.DEBUG)
        self.msg(*args, **kwargs)
    # end debug

    def verbose(self, *args, **kwargs):
        kwargs.setdefault('level', logging.VERBOSE)
        self.msg(*args, **kwargs)
    # end verbose

    def info(self, *args, **kwargs):
        kwargs.setdefault('level', logging.INFO)
        self.msg(*args, **kwargs)
    # end info

    def warning(self, *args, **kwargs):
        kwargs.setdefault('level', logging.WARNING)
        self.msg(*args, **kwargs)
    # end warning

    def error(self, *args, **kwargs):
        kwargs.setdefault('level', logging.ERROR)
        self.msg(*args, **kwargs)
    # end error

    def critical(self, *args, **kwargs):
        kwargs.setdefault('level', logging.CRITICAL)
        self.msg(*args, **kwargs)
    # end critical
# end Logger


class Observer(log.FileLogObserver):
    """
    Observer which can be used to send log messages to different
    streams.  This class also handles terminal color conversion, log
    rollover, and parent directory creation in the case of log files.

    :type stream: string or file
    :param stream:
        The filepath or file stream to log to.
    """
    STREAMS = {}

    def __init__(self, stream=sys.stdout):
        self.addcolor = False

        if stream in (sys.stdout, sys.stderr) and ENABLE_TERMCOLOR:
            self.addcolor = True

        # if the value provided is a string then
        # we need to create a logfile
        if isinstance(stream, (str, unicode)):
            if stream in self.STREAMS:
                pass

            elif not os.path.isfile(stream):
                # first make the directory if it does not exist
                dirname = os.path.dirname(stream)
                if not os.path.isdir(dirname):
                    os.makedirs(dirname)

            # if the file already exists roll it over first
            else:
                rotating = RotatingFileHandler(
                    stream, backupCount=BACKUP_COUNT
                )
                rotating.doRollover()
                logger.debug("rolling over existing log: %s" % stream)

            _stream = stream
            stream = open(_stream, 'a')
            if _stream not in self.STREAMS:
                logger.info("started logging to: %s" % stream.name)
                self.STREAMS[_stream] = stream

        self.stream = stream
        log.FileLogObserver.__init__(self, stream)
    # end __init__

    def emit(self, eventDict):
        """formats the incoming and passes it onto the attached stream"""
        text = log.textFromEventDict(eventDict)
        if text is None:
            return

        timeStr = self.formatTime(eventDict['time'])
        fmtDict = {
            'system': eventDict['system'],
            'text': text.replace("\n", "\n\t")
        }

        # assign the level in the format dict and retrieve
        # the proper level for either the given name or integer
        level = logging.DEBUG
        if 'level' in eventDict:
            if isinstance(eventDict['level'], int):
                fmtDict['level'] = logging.getLevelName(eventDict['level'])
                level = eventDict['level']

            elif isinstance(eventDict['level'], (str, unicode)):
                level = fmtDict['level'] = eventDict['level'].upper()
                if level in logging._levelNames:
                    level = logging.getLevelName(level)

        if fmtDict['system'] is not None:
            msgStr = log._safeFormat(
                "%(level)-8s  [%(system)s] %(text)s\n", fmtDict
            )
        else:
            msgStr = log._safeFormat(
                "%(level)-8s %(text)s\n", fmtDict
            )

        message = timeStr + " " + msgStr

        # add color if it's enabled and the level we are
        # logging has a TERMCOLOR assigned
        if self.addcolor and level in TERMCOLOR:
            prefix, suffix = TERMCOLOR[level]
            message = prefix + message + suffix

        log.util.untilConcludes(self.write, message)
        log.util.untilConcludes(self.flush)
    # end emit

    def __call__(self, eventDict):
        self.emit(eventDict)
    # end __call__
# end Observer

logger = Logger('pyfarm')
logger.startLogger()
