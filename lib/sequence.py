'''
AUTHOR: Oliver Palmer
INITIAL: Nov. 4 2008
PURPOSE: To generate a sequence of frames
OVERVIEW: Given information from the user the sequence class 
first creates number sequence.  After generating the number
sequence based on sFrame, eFrame, bFrame, and padding it is
processed in the make function.  This adds the prefix (if requred),
name, and extension and then outputs it to a list to be used by the
main program.
'''

import sys

class Sequence(object):
	'''
	Sequence class used to generate a frame sequence
		  Variables:
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
		self.padding = padding+1 # add one so padding really does == padding
		self.sFrame = sFrame
		self.eFrame = eFrame
		self.bFrame = bFrame
		self.ext = ext
		
	def seq(self):
	    '''Generate a list of padded numbers'''
	    for num in xrange(self.sFrame, self.eFrame+1, self.bFrame):
	    	yield str(num).zfill(self.padding)
		
	def make(self, prefix=True):
		'''
		Given input from user, generate the complete frame sequence
		
		OPTIONS:
			prefix -- allows user enable or disable the prefix
		'''
		for num in self.seq():
			yield num
			
        
class Frame(object):
	'''
	Frame class used to gather information from a specific frame
		Variables:
			self.sequence = Input sequence list
			self.frame    = Frame number to query
	'''
	def __init__(self, sequence, frameNum):
		self.sequence = sequence
		self.frameNum = frameNum
		
	def exists(self):
		'''Check to see if the requested frame exists in the sequence'''
		pass
		
	def prefix(self):
		'''Return frame prefix from sequence'''
		pass
		
	def name(self, mode='medium'):
		'''
		Return the name of the frame according to the mode
			Modes:
				small  -- name only
				medium -- name, frameNumber, and extension
				large  -- everything including the prefix
		'''
		pass
		
	def number(self, zeros=True):
		'''
		Return the current frame number in the sequence
			Variable:
				zeros -- Will return either a number with or without zeros
		'''
		# using int(num) will get rid of the 00's in the string
		pass
		
	def extension(self):
		'''Return the extension to the user from the sequence'''
		pass
		
	def step(self):
		'''Generate the sequence step and return it to the user'''
		# take two concurrent frames, calculate the diff.  Do it again
		# then average the two
		
class Info(object):
	'''
	Query information about a sequence, return it to the user
		Modes:
			frames     -- return the total number of frames in the sequence
			extensions -- return all extensions in the sequence
			prefixes   -- return all prefixes used in the sequence
	'''
	def __init__(self, mode):
		self.mode = mode
		
	def get(self):
		if self.mode == 'frames':
			pass
		elif self.mode == 'extensions' or 'exts':
			pass
		elif self.mode == 'prefixes' or 'prefix':
			pass
			
if __name__ == '__main__':
	program = sys.argv[0]
	print "\n%s is meant to be imported" % program
	print "Please run help(%s) from python to learn more about this module\n" % program
