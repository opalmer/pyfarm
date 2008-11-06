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

class ThreadRenderQue(object):
	def __init__(self, threads, rfile):
		self.Pool    = []
		self.cmdList = []
		self.Qin     = Queue.Queue()
		self.Qout    = Queue.Queue()
		self.Qerr    = Queue.Queue()
		self.threads = int(threads)
		self.frames  = []
		self.driver  = ''
		self.rfile   = ''
		self.flag    = ''

	def addFile(self, rFile):
		'''Prepare a project file to build'''
		self.rFile = rFile
		
	def isFile(self, fType):
		'''
		Returns true or false based on fType
		Example:
			if fType is == self.rFile:
				return True
		'''
		if self.rFile.split('.')[len(self.rFile.split('.'))-1] != fType:
			return True
		else:
			return False
		
	def addFrames(self, sFrame, eFrame, bFrame):
		'''
		Prepare a frame range to build
		Vars:
			sFrame -- Start Frame
			eFrame -- End Frame
			bFrame -- By Frame
		'''
		self.frames = range(int(sFrame),int(eFrame)+1,bFrame)
##
	def addDriver(self, driver):
		'''Prepare a driver to build[Houdini Only]'''
		if isFile('hip'):
			self.driver = driver
		else:
			print "You can only add an output driver if you are rendering with houdini"
		
	def addFlag(self, flag):
		'''Add a specific render flag to the command'''
		self.flag = flag
		
	def buildCommands(self, render):
		'''Query frames, driver, and file then build a command list'''
		pass
		
	def reportError(self):
		'''Add errors into the Qerr instance for the user to evaluate'''
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
			command, item = self.Qin.get()       # implicitly stops and waits
			if command == 'stop':
				break
			try:
				# simulated work functionality of a worker thread
				if command == 'process':
					result = os.system('%s' % item)
				else:
					raise ValueError, 'Unknown command %r' % command
			except:
			        # unconditional except is right, since we report _all_ errors
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

def help( mode ):
	'''
	Return help information about the program\n
	in the event sys.argv != 6
	'''
	os.system('clear')
	program = sys.argv[0]

	if mode == 'usage':
		print "\nPROGRAM:\n\t%s" % program
		print "\nERROR:\n\tIncorrect number of parameters specified\n\tPlease see usage and examples below"
		print "\nUSAGE:\n\t%s startFrame endFrame numberOfThreads outputDriver hipFile" % program
		print "\nEXAMPLE:\n\t%s 1 250 4 mantra P2_seq1_v4.hip" % program
		print "\nHINTS:\n\t-For non-threaded processes (i3d bakes) set the number of \n\tthreads to AT LEAST equal to the number of processes"
		print "\n\t-Do not offset the start frame, python will handle that when creating the threads"
	sys.exit(1)

def main():
	'''Main program functions, insert pythonic code here'''
	Que = ThreadRenderQue(hip,driver,threads)
	Que.addFrames()
	Que.addDriver()

if __name__ == '__main__':
	if len(sys.argv) != 6:
		help('usage')
	else:
		main()
