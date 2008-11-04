'''
AUTHOR: Oliver Palmer
INITIAL: Nov. 4 2008
PURPOSE: To generate a sequence of frames
OVERVIEW: This module is meant to generate a frame sequence based on several input parameters
'''

class Sequence(object):
	'''Sequence class used to generate a frame sequence'''
	def __init__(self, name, padding, prefix, suffix):
		self.name = name
		self.padding = str(padding)
		self.prefix = prefix
		self.suffix = suffix

def main():
	'''Main program loop, this is run if nothing else is imported'''
	print "Hello world"

if __name__ == '__main__':
	import sys
	program = sys.argv[0]
	print "\n%s is meant to be imported" % program
	print "Please run help(%s) from python to learn more about this module\n" % program

else:
	main()
