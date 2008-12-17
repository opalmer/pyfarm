'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 16 2008
PURPOSE: Logging scripts used to inform the user of extra information.
'''
import sys
import string
import logging

def level(level):
    '''
    Used to define the maxium level for logging

    VARIABLES:
    level -- maxium level to  log
    '''
    if string.upper(level) == 'DEBUG':
        logger.setLevel(logging.DEBUG)
        handler.setLevel(logging.DEBUG)

    elif string.upper(level) == 'INFO':
        logger.setLevel(logging.INFO)
        handler.setLevel(logging.INFO)

    elif string.upper(level) == 'WARNING':
        logger.setLevel(logging.WARNING)
        handler.setLevel(logging.WARNING)

    elif string.upper(level) == 'ERROR':
        logger.setLevel(logging.ERROR)
        handler.setLevel(logging.ERROR)

    elif string.upper(level) == 'CRITICAL':
        logger.setLevel(logging.CRITICAL)
        handler.setLevel(logging.CRITICAL)

def setup(lvl='DEBUG',name='PyFarm'):
    '''
    Setup and prepare for logging

    VARIABLES:
        name (string) - Used to help inform the user where the information
                        is coming from.
        lvl (string) - maxium level of logging to display
    '''
    global logger
    global format
    global handler
    logger = logging.getLogger(name)
    handler = logging.StreamHandler()

    level(lvl)

    format = logging.Formatter("%(asctime)s - %(levelname)s - %(name)s - %(message)s")
    handler.setFormatter(format)
    logger.addHandler(handler)

def debug(msg):
    '''Send debugging information to the user'''
    logger.debug(msg)

def info(msg):
    '''Send information about an opperation to the user'''
    logger.info(msg)

def warning(msg):
    '''Send a warning to the user'''
    logger.warning(msg)

def error(msg):
    '''Send any error messages to the user'''
    logger.error(msg)

def critical(msg):
    '''Send critical messages to the user'''
    logger.critical(msg)