#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Nov 8 2008
PURPOSE: To Test lib.hrenderThreaded_v3
ORDER OF OPERATIONS:
    1.) Setup variables
    2.) Generate one command per frame
    3.) Start N threads based on que.countCores()
    4.) When finished, stop all threads
    5.) Show all results (Optional, but good for diagnostics)
    6.) Show all errors (Optional, but good for diagnostics)
    7.) Exit

NOTE: lib.hrenderThreaded_v3 COULD be run on its own however running it this
way is much cleaner.  Also if you run directly via command line you cannot
customize the number of threads by default.
'''
import os
import sys
import lib.ThreadedRenderQue as que


# initial setup variables
sFrame  = 1
eFrame  = 100
bFrame  = 1
driver  = 'i3d_gen_v60'
hFile   = '/media/projects/graphics/VSFX428-Particles/P2-DustStorm/dust_storm.hip'

for num in range(sFrame,eFrame+1,bFrame):
    que.requestWork('hrender -e -f %s %s -d %s %s' % (num,num,driver,hFile))

que.startThreads(que.countCores()) # <- Alternatively enter a custom thread number
que.stopThreads()
#que.showResults()
#que.showErrors()
sys.exit(0)
