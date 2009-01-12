'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 11 2008
PURPOSE: Module used to manage ques inside of the program
'''
import Queue
# TODO: Create a working child of the queue standard module

class Que( Queue.Queue() ):
    '''Create and manage a custom custom que in program'''
    def __init__(self):
        Queue.__init__(self)

    def put(self, item, block=True, timeout=None):
        '''Put an item into the que'''
        try:
            self.put(item)

        except Full:
            FarmLog.info('Que is full')

    def get(self):
        '''Get a job from the que'''
        return self.get()
