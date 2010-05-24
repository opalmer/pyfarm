'''
HOMEPAGE: www.pyfarm.net
INITIAL: May 22 2010
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

import sys
import time

__LOGLEVEL__ = 4
__MODULE__ = "lib.Logger"

class Logger(object):
	'''
	Custom logging object for PyFarm

	VARS:
		level (int) -- minimum level to log
		solo (bool) -- If set the true only requests matching level will be served
		logfile (str) -- file to log to
	'''
	def __init__(self, name, level=5, logfile=None, solo=False):
		self.level = level
		self.solo = solo
		self.timeFormat = "%Y-%m-%d %H:%M:%S"
		self.levelList = ['SQLITE','NETPACKET','QUEUE','CONDITIONAL',
		'DEBUG','INFO','WARNING','ERROR','CRITICAL','FATAL']

		self.setName(name)
		self.setLevel(level)

		if logfile:
			self.logfile = open(logfile, "a")
		else:
			self.logfile = None

		self.debug("Logger module setup")

	def _out(self, level, msg):
		'''Perform final formatting and output the message to the appropriate locations'''
		out = "%s - %s - %s - %s" % (time.strftime(self.timeFormat), level, self.name, msg)
		if level in self.levels:
			print out
			if self.logfile:
				self.logfile.write(out+"\n")
				self.logfile.flush()

	def close(self):
		'''Close out the log file'''
		self.logfile.close()

	def setName(self, name):
		'''Set the name for the logger'''
		self.name = name

	def setLevel(self, level):
		'''Set the level and configure the level list'''
		self.level = level
		if not self.solo:
			self.levels = self.levelList[self.level:]
		else:
			self.levels = self.levelList[self.level]

	def setSolo(self, solo):
		'''If set to 1 only the logLevel matching solo will be output'''
		self.solo = solo
		self.levels = self.levelList[self.solo]

	def sqlite(self, msg):
		'''Print a sqlite message'''
		self._out(self.levelList[0], msg)

	def netpacket(self, msg):
		'''Print a netpacket message'''
		self._out(self.levelList[1], msg)

	def queue(self, msg):
		'''Print a queue message'''
		self._out(self.levelList[2], msg)

	def conditional(self, msg):
		'''Print a conditional message'''
		self._out(self.levelList[3], msg)

	def debug(self, msg):
		'''Print a debug message'''
		self._out(self.levelList[4], msg)

	def info(self, msg):
		'''Print an info message'''
		self._out(self.levelList[5], msg)

	def warning(self, msg):
		'''Print a warning message'''
		self._out(self.levelList[6], msg)

	def error(self, msg):
		'''Print an error message'''
		self._out(self.levelList[7], msg)

	def critital(self, msg):
		'''Print a critical message'''
		self._out(self.levelList[8], msg)

	def fatal(self, msg):
		'''Print a fatal error message and exit'''
		self._out(self.levelList[9], msg)
		sys.exit(1)


if __name__ == '__MAIN__':
	log = Logger("LogTest", __LOGLEVEL__, logfile="testlog.log")

	# now for a test
	i = 0
	while i < 1000:
		log.sqlite("This is a sqlite message")
		log.netpacket("This is a netpacket message")
		log.queue("This is a queue message")
		log.conditional("This is a conditional message")
		log.debug("This is a debug message")
		log.info("This is a info message")
		log.warning("This is a warning message")
		log.error("This is a error message")
		log.critital("This is a critital message")
		log.fatal("This is a fatal message")
		i += 1