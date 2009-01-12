'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com || (703)725-6544
INITIAL: Dec 15 2008
PURPOSE: Module used to control the output of logging
'''

# TODO: Add logging to file as well as or in place of console logging
# TODO: Create SPEFIFIC logging for rendering

import string
import logging

class FarmLog(object):
    '''
    Used to setup a custom logging program for PyFarm

    REQUIRES:
        Python:
            string
            logging

    INPUT:
        program (string) -- set the name of the logging program
        level (string) -- set the maxium logging level
    '''
    def __init__(self, program='PyFarm', level='debug'):
        super(FarmLog, self).__init__()
        self.level = string.upper(level)

        #create logger
        self.logger = logging.getLogger(program)
        self.ch = logging.StreamHandler()
        self.logger.setLevel(level)

        #create formatter
        formatter = logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
        #add formatter to ch
        self.ch.setFormatter(formatter)
        #add ch to logger
        self.logger.addHandler(self.ch)

    def setProgram(self, prog):
        '''
        Set the name of the logging program, after the defaults
        have been setup inside of __init__

        INPUT:
            prog (string) -- The name to set the logger to
        '''
        self.logger = logging.getLogger(prog)

    def setLevel(self, level):
        '''
        Set the maxium logging level.  Can also set the level
        post initial setup.

        INPUT:
            level (string) [debug] -- set the max logging level
        '''
        self.level = string.upper(level)
        levels = ['CRITICAL','ERROR','WARNING','INFO','DEBUG']

        if self.level not in levels:
            raise '\n\n[ BREAK ] That log level is NOT valid!'
        else:
            if self.level == 'CRITICAL':
                self.logger.setLevel(logging.CRITICAL)
                self.ch.setLevel(logging.CRITICAL)
            elif self.level == 'ERROR':
                self.logger.setLevel(logging.ERROR)
                self.ch.setLevel(logging.ERROR)
            elif self.level == 'WARNING':
                self.logger.setLevel(logging.WARNING)
                self.ch.setLevel(logging.WARNING)
            elif self.level == 'INFO':
                self.logger.setLevel(logging.INFO)
                self.ch.setLevel(logging.INFO)
            elif self.level == 'DEBUG':
                self.logger.setLevel(logging.DEBUG)
                self.ch.setLevel(logging.DEBUG)

    def critical(self, msg):
        '''
        Echo critical level messages to self.logger

        INPUT:
            msg (string) -- The message to send to the logger
        '''
        self.logger.critical(msg)

    def error(self, msg):
        '''
        Echo error level messages to self.logger

        INPUT:
            msg (string) -- The message to send to the logger
        '''
        self.logger.error(msg)

    def warning(self, msg):
        '''
        Echo warning messages to self.logger

        INPUT:
            msg (string) -- The message to send to the logger
        '''
        self.logger.warning(msg)

    def info(self, msg):
        '''
        Echo info messages to self.logger

        INPUT:
            msg (string) -- The message to send to the logger'''
        self.logger.info(msg)

    def debug(self, msg):
        '''
        Echo debug messages to self.logger

        INPUT:
            msg (string) -- The message to send to the logger
        '''
        self.logger.debug(msg)
