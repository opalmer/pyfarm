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

def coreCount():
	'''Find the number of cores on a linux workstation, add self.eThreads'''
	return int(os.system('cat /proc/cpuinfo | grep processor | wc -l'))

class ThreadedRenderQue(object):
	'''
	Que for defining and starting a threaded render que.
	For an example of usage see the main function
	'''
	def __init__(self, sFrame, eFrame, bFrame, driver, hFile):
		'''
		Initialize all variables:
			self.Pool     = List containing all threads
			self.cmdList  = List containing a command list
			self.Qin      = Generator containing input que
			self.Qout     = Generator containing output que
			self.Qerr     = Generator containing error que
			self.sFrame   = Start frame integer
			self.eFrame   = End frame integer
			self.bFrame   = By frame integer
			self.driver   = Houdini output driver
			self.hFile    = Houdini HIP file
		'''
		self.Pool    = []
		self.cmdList = []
		self.Qin     = Queue.Queue()
		self.Qout    = Queue.Queue()
		self.Qerr    = Queue.Queue()
		self.sFrame  = int(sFrame)
		self.eFrame  = int(eFrame)+1
		self.bFrame  = int(bFrame)
		self.driver  = driver
		self.hFile   = hFile

	def build(self):
		'''Build command list from __init__ vars'''
		for num in range(self.sFrame, self.eFrame, self.bFrame):
			self.cmdList.append('hredner -e -f %s %s -d %s %s' % (num,num,self.driver,self.hFile))
		print self.cmdList
		print coreCount()

	def reportError(self):
		'''Output any and all errors to the Qerr generator instanace'''
		return self.Qerr.put(sys.exc_info()[:2])

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
			command, item = self.Qin.get() # implicitly stops and waits
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
					self.Qout.put(result)

	def startThreads(self, numThreads=5, daemons=True):
		'''Create N threads, daemonize, then run all threads'''
		for i in range(numThreads):
			newThread = threading.Thread(target=doWork)
			newThread.setDaemon(daemons)
			Pool.append(newThread)
			newThread.start()

	def getWork(self, data, command='process'):
		'''Post work requests as (command,data) to Qin'''
		self.Qin.put((command, data))

	def getResults(self):
		'''Get the final results from queue'''
		return self.Qout.get()

	def showResults(self):
		'''Display the results'''
		for result in yieldQueue(self.Qout):
			print 'Result:', result

	def showErrors(self):
		'''Display the errors'''
		for etyp, err in reportError(self.Qerr):
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