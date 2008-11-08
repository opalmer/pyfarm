#!/usr/bin/python
# PURPOSE: To Test hrender

import sys
import lib.hrenderThreaded_v3 as que

sFrame  = 1
eFrame  = 250
bFrame  = 1
driver  = 'i3d_output'
hFile   = 'test.hip'
cmdList = []

def generateCommands():
	'''Generate a complete list of commands'''
	for num in range(sFrame,eFrame,bFrame):
		yield 'hrender -e -f %s %s -d %s %s' % (num,num,driver,hFile)

for command in generateCommands():
	que.requestWork( command )

que.startThreads(que.countCores)
que.stopThreads()
que.showResults()
que.showErrors()
sys.exit(0)