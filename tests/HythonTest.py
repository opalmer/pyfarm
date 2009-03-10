# USAGE: hython HythonTest.py

hou.hipFile.load('/media/projects/graphics/TECH420/P3_Final/houdini/P3_palmer_scene_v8.hip')

for n in hou.node('/out').children():
	print n
