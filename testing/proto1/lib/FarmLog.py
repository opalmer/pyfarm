'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 15 2008
PURPOSE: Module used to control the output of logging
'''

# TODO: Add logging to file as well as or in place of console logging
# TODO: Create SPEFIFIC logging for rendering

import string
import logging

class FarmLog(object):
    def __init__(self, program='PyFarm', level='debug'):
        super(FarmLog, self).__init__()
        self.level = string.upper(level)

        #create logger
        self.logger = logging.getLogger(program)
        self.logger.setLevel(self.setLevel(self.level))
        #create console handler and set level to debug
        self.ch = logging.StreamHandler()
        self.ch.setLevel(self.setLevel(self.level))
        #create formatter
        formatter = logging.Formatter("%(asctime)s - %(name)s - %(levelname)s - %(message)s")
        #add formatter to ch
        self.ch.setFormatter(formatter)
        #add ch to logger
        self.logger.addHandler(self.ch)

    def setProgram(self, prog):
        '''All the user to mod the program name'''
        self.logger = logging.getLogger(prog)

    def setLevel(self, level='debug'):
        '''Set the maxium logging level'''
        levels = ['CRITICAL','ERROR','WARNING','INFO','DEBUG']
        self.level = string.upper(level)

        if self.level not in levels:
            raise '\n\n[ BREAK ] That log level is NOT valid!'
        else:
            if self.level == 'CRITICAL':
                return logging.CRITICAL
            elif self.level == 'ERROR':
                return logging.ERROR
            elif self.level == 'WARNING':
                return logging.WARNING
            elif self.level == 'INFO':
                return logging.INFO
            elif self.level == 'DEBUG':
                return logging.DEBUG

    def critical(self, msg):
        '''Echo critical level messages to self.logger'''
        self.logger.critical(msg)

    def error(self, msg):
        '''Echo error level messages to self.logger'''
        self.logger.error(msg)

    def warning(self, msg):
        '''Echo warning messages to self.logger'''
        self.logger.warning(msg)

    def info(self, msg):
        '''Echo info messages to self.logger'''
        self.logger.info(msg)

    def debug(self, msg):
        '''Echo debug messages to self.logger'''
        self.logger.debug(msg)
