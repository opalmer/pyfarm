#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
INITIAL: Nov 6 2008
PURPOSE: To bring multi-threaded rendering to non threaded renders
'''
import threading, Queue, time, sys, os

Qin  = Queue.Queue()
Qout = Queue.Queue()
Qerr = Queue.Queue()
Pool = []

def reportError():
	'''we report errors by adding error information to Qerr'''
	Qerr.put(sys.exc_info()[:2])

def getAllWork(Q):
	'''generator to yield one after the others all items currently
	in the Queue Q, without any waiting
	'''
	try:
		while True:
			yield Q.get_nowait()
	except Queue.Empty:
		raise StopIteration

def doWork():
	'''Go get some work, then work on it'''
	while True:
		command, item = Qin.get()       # implicitly stops and waits
		if command == 'stop':
			break
		try:
			if command == 'process':
				result = os.system('%s' % item) # <-- place your command here
			else:
				raise ValueError, 'Unknown command %r' % command
		except:
			reportError()
		else:
			Qout.put(result)

def startThreads(number_of_threads_in_pool=5, daemons=True):
	''' make a pool of N worker threads, daemonize, and start all of them '''
	for i in range(number_of_threads_in_pool):
		 new_thread = threading.Thread(target=doWork)
		 new_thread.setDaemon(daemons)
		 Pool.append(new_thread)
		 new_thread.start()

def requestWork(data, command='process'):
	''' work requests are posted as (command, data) pairs to Qin '''
	Qin.put((command, data))

def getResult():
	'''Stop and wait for results'''
	return Qout.get()

def showResults():
	'''Show all results inside of Qout'''
	for result in getAllWork(Qout):
		print 'Result:', result

def showErrors():
	'''Show allerrors inside of Qerr'''
	for etyp, err in getAllWork(Qerr):
		print 'Error:', etyp, err

def stopThreads():
	'''
	Stop the thread pool and then free all threads
	ORDER IS IMPORTANT!
	Order of Operations:
		1.) Request all threads to stop working
		2.) Wait for each thread to terminate
		3.) Clean up the thread pool @ Pool[]
	'''
	for i in range(len(Pool)):
		requestWork(None, 'stop')

	for existing_thread in Pool:
		existing_thread.join()

	del Pool[:]

if __name__ == '__main__':
	# run this code only if run via the command line
	# check to make sure the user has input the correct number of commands
	if len(sys.argv) != 7:
		os.system('clear')
		print "\nPROGRAM:\n\t%s" % sys.argv[0]
		print "\nERROR:\n\tIncorrect number of parameters specified\n\tPlease see usage and examples below"
		print "\nUSAGE:\n\t%s startFrame endFrame byFrane threads outputDriver hipFile" % sys.argv[0]
		print "\nEXAMPLE:\n\t%s 1 250 1 4 i3d_output_bake P2_seq2_v4.hip" % sys.argv[0]
		sys.exit(1)
	else:
		sFrame  = int(sys.argv[1])
		eFrame  = int(sys.argv[2])+1
		bFrame  = int(sys.argv[3])
		threads = int(sys.argv[4])
		driver  = sys.argv[5]
		hFile   = sys.argv[6]
		cmdList = []

		for num in range(sFrame,eFrame):
			cmdList.append('hrender -e -f %s %s -d %s %s' % (num,num,driver,hFile))

		for command in cmdList:
			requestWork(command) # add the command from cmdList into the que

		startThreads(threads)    # start the processes with a spec. number of threads
		stopThreads()            # cleanup threads
		showResults()            # show all results
		showErrors()             # show any errors that occur inside of python
		sys.exit(0)
