#!/usr/bin/python
# PURPOSE: To Test hrender

import sys
import lib.hrenderThreaded_v3 as que

sFrame  = 1
eFrame  = 30
bFrame  = 1
threads = 8
driver  = 'i3d'
hFile   = '/media/projects/graphics/VSFX428-Particles/P2-DustStorm/dust_storm.hip'

def countThreads():

for num in range(sFrame,eFrame+1,bFrame):
	que.requestWork('hrender -e -f %s %s -d %s %s' % (num,num,driver,hFile))

que.startThreads(threads) # <- enter the num
que.stopThreads()
#que.showResults()
#que.showErrors()
#sys.exit(0)