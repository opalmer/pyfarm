'''
AUTHOR: Oliver Palmer
INITIAL: Nov. 4 2008
PURPOSE: To generate a sequence of frames
OVERVIEW: This module is meant to generate a frame sequence based on several input parameters
'''

class Sequence(object):
	'''Sequence class used to generate a frame sequence
		  Variable Help:
  			self.prefix  = output folder of sequence ex. /output/here/frame.001.exr
			self.name    = Name of the frame
			self.padding = Number 0s before frame number
			self.sFrame  = Start frame of sequence
			self.eFrame  = End frame of sequence
			self.bFrame  = By frame of sequence
			self.ext     = extension of frame sequence
	'''
	def __init__(self, prefix, name, padding, sFrame, eFrame, bFrame, ext):
		self.prefix = prefix
		self.name = name
		self.padding = padding+1
		self.sFrame = sFrame
		self.eFrame = eFrame
		self.bFrame = bFrame
		self.ext = ext
		
	def seq(self):
	    '''Generate a list of padded numbers based on user input'''
	    for num in xrange(self.sFrame, self.eFrame+1, self.bFrame):
	    	yield str(num).zfill(self.padding)
		
	def make(self):
		'''Make a complete sequence for render processing'''
		for num in self.seq():
			yield num
			
        
if __name__ == '__main__':
	import sys
	program = sys.argv[0]
	print "\n%s is meant to be imported" % program
	print "Please run help(%s) from python to learn more about this module\n" % program
