#!/usr/bin/python
# PURPOSE: To Test hrender

import sys
from lib.hrenderThreaded_v2 import ThreadedRenderQue

que = ThreadedRenderQue(1,10,1,"i3d_output","thisFile.hip")

# get work from built commands
que.getWork(que.buildCommands())

# start the thread pool
#  number of threads is based on the number of processors avaliable to the system
que.startThreads()

# stop an free threads
que.freeThreads()

# show all results and errors
que.showResults()
que.showErrors()

sys.exit(0)
