#!/usr/bin/python
# PURPOSE: To Test hrender

from lib.hrenderThreaded_v2 import ThreadedRenderQue
que = ThreadedRenderQue(1,10,1,"i3d_output","thisFile.hip")
#print que.coreCount()
print que.build()