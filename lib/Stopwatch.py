'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Nov 11 2008
PURPOSE: To provide various timining and stopwatch like functions to python.
'''

import time

# TODO: Test the stopwatch and add proper logging to it
class Stopwatch(object):
    def __init__(self):
        self.start   = 0.0
        self.end     = 0.0
        self.elapsed = 0.0

    def start(self):
        '''Start the stopwatch'''
        self.start = time.time()

    def stop(self):
        '''Stop the given lap'''
        self.end = time.time()

    def reset(self):
        '''Rest the given lap'''
        pass

    def elapsed(self):
        '''Return the elapsed time for the given lap'''
        return self.elapsed = self.start - self.end

if __name__ == '__main__':
    print "Sorry, this module is meant to be imported"
