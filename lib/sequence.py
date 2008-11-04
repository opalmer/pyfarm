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
		
	def fullName(self):
		'''Retun the frame name without the prefix'''
		pass
		
if __name__ == '__main__':
	import sys
	program = sys.argv[0]
	print "\n%s is meant to be imported" % program
	print "Please run help(%s) from python to learn more about this module\n" % program
