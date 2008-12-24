#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 23 2008
PURPOSE: Class used for general testing of Prototype 1
'''

from lib.FarmLog import FarmLog

log = FarmLog('Test')
log.setLevel('debug')
log.critical('Hello world')
