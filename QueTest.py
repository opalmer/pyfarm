#!/usr/bin/python

from lib.Que import *

a = PriorityQueue()
ray = True

if ray:
    ray_cmd = '-r mr -v 5'
else:
    ray_cmd = ''

scene = 'scene.mb'
cmd ='/usr/bin/autodesk/maya2009-x64/bin/Render'

for i in range(1, 3000+1):
    out = '%s -s %i -e %i %s %s' % (cmd, i, i, ray_cmd, scene)
    a.put(out)
    
while not a.empty():
    print a.get()
    
print "Empty"
