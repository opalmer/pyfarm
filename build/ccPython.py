#!/usr/bin/python
# PURPOSE: To compile python code into by-code

from os import popen
from py_compile import compile

scripts = popen('find `bzr ls` -name "*.py" | grep -v books/network_programming')

print "Now compiling..."
for script in scripts:
	script = script.strip('\n')
	print "...%s" % script
	compile( script )
	print "done!\n"
