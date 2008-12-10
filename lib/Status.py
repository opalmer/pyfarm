'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Nov 11 2008
PURPOSE: File to return error messages and status code interpretation to
the end user.
'''

import sys
import time
import logging

class Exit(object):
	'''Exit class used to describe exit codes to end user'''
	def __init__(self):
		pass

	def exit(self, code):
		'''Explain the reason for exiting the to the user'''
		if code == 0:
			print "[ STATUS ] Program Terminating -- Operation Normal"
			sys.exit(code)

		elif code == 1:
			print "[ ERROR ] Program Terminating -- Invalid Input"

class Error(object):
	'''Custom error class used to handle exceptions and faults'''
	def __init__(self):
		pass

	def format(self):
		'''Format a custom logging string'''
		pass

class Log(object):
	'''Class to generate in program logging'''
	def __init__(self):
		pass

	def config(self, message):
		'''Configure the logging structure given a string message'''
		pass

if __name__ == '__main__':
	print "This module is not meant to be run directly, please import it"
	sys.exit(1)