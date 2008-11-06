#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
Based on a cookbook example by John Nielsen & Justin A.
#SEND TO STEVE WAGNER
'''
import threading, Queue, time, sys, os

# Globals (start with a capital letter)
Qin  = Queue.Queue()
Qout = Queue.Queue()
Qerr = Queue.Queue()
Pool = []

def report_error():
    ''' we "report" errors by adding error information to Qerr '''
    Qerr.put(sys.exc_info()[:2])

def get_all_from_queue(Q):
    ''' generator to yield one after the others all items currently
        in the Queue Q, without any waiting
    '''
    try:
        while True:
            yield Q.get_nowait()
    except Queue.Empty:
        raise StopIteration

def do_work_from_queue():
    ''' the get-some-work, do-some-work main loop of worker threads '''
    while True:
        command, item = Qin.get()       # implicitly stops and waits
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
            report_error()
        else:
            Qout.put(result)

def make_and_start_thread_pool(number_of_threads_in_pool=5, daemons=True):
    ''' make a pool of N worker threads, daemonize, and start all of them '''
    for i in range(number_of_threads_in_pool):
         new_thread = threading.Thread(target=do_work_from_queue)
         new_thread.setDaemon(daemons)
         Pool.append(new_thread)
         new_thread.start()

def request_work(data, command='process'):
    ''' work requests are posted as (command, data) pairs to Qin '''
    Qin.put((command, data))

def get_result():
    return Qout.get()     # implicitly stops and waits

def show_all_results():
    for result in get_all_from_queue(Qout):
        print 'Result:', result

def show_all_errors():
    for etyp, err in get_all_from_queue(Qerr):
        print 'Error:', etyp, err

def stop_and_free_thread_pool():
    # order is important: first, request all threads to stop...:
    for i in range(len(Pool)):
        request_work(None, 'stop')

    # ...then, wait for each of them to terminate:
    for existing_thread in Pool:
        existing_thread.join()

    # clean up the pool from now-unused thread objects
    del Pool[:]

program = sys.argv[0]
if len(sys.argv) != 6:
	# Display help if the user does not enter the correct number of arguments
	os.system('clear')
	print "\nPROGRAM:\n\t%s" % program
	print "\nERROR:\n\tIncorrect number of parameters specified\n\tPlease see usage and examples below"
	print "\nUSAGE:\n\t%s startFrame endFrame numberOfThreads outputDriver hipFile" % program
	print "\nEXAMPLE:\n\t%s 1 250 4 mantra P2_seq1_v4.hip" % program
	print "\nHINTS:\n\t-For non-threaded processes (i3d bakes) set the number of \n\tthreads to AT LEAST equal to the number of processes"
	print "\n\t-Do not offset the start frame, python will handle that when creating the threads"
	sys.exit(1)
else:
	sFrame  = int(sys.argv[1])
	eFrame  = int(sys.argv[2])+1
	threads = int(sys.argv[3])
	driver  = sys.argv[4]
	hFile   = sys.argv[5]
	cmdList = []
	
	for num in range(sFrame,eFrame):
		cmdList.append('hrender -e -f %s %s -d %s %s' % (num,num,driver,hFile))
	
	for command in cmdList:	
		request_work(command)       # add the command from cmdList into the que
	
	make_and_start_thread_pool(threads) # start the processes with a spec. number of threads
	stop_and_free_thread_pool()         # cleanup threads
#	show_all_results()                  # show all results
	show_all_errors()                   # show any errors that occur inside of python
	sys.exit(0)
