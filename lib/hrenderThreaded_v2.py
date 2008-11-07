#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
INITIAL: Nov 5 2008
PURPOSE: To render an hrender process as a threaded task
OVERVIEW: Some renders, such as i3d file generation, by default are unthreaded tasks.
The basic concept to this program is to gather input from the user and created several
threaded renders thereby reducing the overall time to render a sequence on one workstation.
'''

import os
import sys
import Queue
import os.path
import threading

Qin  = Queue.Queue()
Qout = Queue.Queue()
Qerr = Queue.Queue()

def coreCount():
	'''Find the number of cores on a linux workstation'''
	return int(os.system('cat /proc/cpuinfo | grep processor | wc -l'))

class ThreadedRenderQue(object):
	'''Que for defining and starting a threaded render locally'''
	def __init__(self, sFrame, eFrame, bFrame, driver, hFile):
		'''
		Initialize all variables:
			self.Pool     = List containing all threads
			self.cmdList  = List containing a command list
			self.sFrame   = Start frame integer
			self.eFrame   = End frame integer
			self.bFrame   = By frame integer
			self.driver   = Houdini output driver
			self.hFile    = Houdini HIP file
		'''
		self.Pool    = []
		self.cmdList = []
		self.sFrame  = int(sFrame)
		self.eFrame  = int(eFrame)+1
		self.bFrame  = int(bFrame)
		self.driver  = driver
		self.hFile   = hFile

	def buildCommands(self):
		'''Build command list from __init__ vars'''
		for num in range(self.sFrame, self.eFrame, self.bFrame):
			self.cmdList.append('hredner -e -f %s %s -d %s %s' % (num,num,self.driver,self.hFile))

	def reportError(self, error):
		'''Output any and all errors to the Qerr generator instanace'''
		return Qerr.put(sys.exc_info()[:2])

	def yieldQueue(self, Q):
		'''Yield all items in the Queue without waiting'''
		try:
			while True:
				yield Q.get_nowait()
		except Queue.Empty:
			raise StopIteration

	def doWork(self):
		'''Create threads and do work'''
		while True:
			command, item = Qin.get() # implicitly stops and waits
			if command == 'stop':
				break
			try:
				# simulated work functionality of a worker thread
				if command == 'process':
					result = os.system('%s' % item)
				else:
					raise ValueError, 'Unknown command %r' % command
			except:
					# unconditional except is right, since we report ALL errors
					reportError()
			else:
					Qout.put(result)

	def startThreads(self, daemons=True):
		'''Create threads based on numThreads(), add them into the pool'''
		for i in range(coreCount()):
			newThread = threading.Thread(target=doWork)
			newThread.setDaemon(daemons)
			Pool.append(newThread)
			newThread.start()

	def getWork(self, data, command='process'):
		'''Post work requests as (command,data) to Qin'''
		Qin.put((command, data))

	def getResults(self):
		'''Get the final results from queue'''
		return Qout.get()

	def showResults(self):
		'''Display the results'''
		for result in self.yieldQueue(Qout):
			print 'Result:', result

	def showErrors(self):
		'''Display the errors'''
		for etyp, err in self.reportError(Qerr):
			print 'Error:', etyp, err

	def freeThreads(self):
		'''Stop and free all threads, nicely.  ORDER MATTERS!'''
		# first ask all threads to stop
		for i in range(len(self.Pool)):
			getWork(None, 'stop')

		# next, wait for each to terminate
		for existingThread in self.Pool:
			existingThread.join()

		# now we can cleaup the thread pool
		del self.Pool[:]

if __name__ == '__main__':
	print "Sorry, this module is meant to be imported"