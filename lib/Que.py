'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 11 2008
PURPOSE: Module used to manage ques inside of the program
'''

import time
import Queue
import heapq

class PriorityQueue(Queue.Queue):
    '''
    Priority based Queue based on Python's Queue.Queue
    '''
    def _init(self, maxsize):
        self.maxsize = maxsize
        self.queue = []
        
    def _qsize(self):
        #Return the number of items that are currently enqueued
        return len(self.queue)
        
    def _empty(self):
        #Check and see if queue is empty
        return not self.queue
        
    def _full(self):
        #Check and see if queue is full
        return self.maxsize > 0 and len(self.queue) >= self.maxsize
        
    def _put(self, item):
        #Put a new item into the que
        heapq.heappush(self.queue, item)
        
    def _get(self):
        #Get an item from the queue
        return heapq.heappop(self.queue)
        
    def put(self, item, priority=0, block=True, timeout=None):
        '''Shadow and wrap Queue's put statement to allow for a priority'''
        decorated_item = priority, time.time(), item
        Queue.Queue.put(self, decorated_item, block, timeout)
        
    def get(self, block=True, timeout=None):
        '''Shadow and wrap Queue own get to strip auxiliary aspsects'''
        priority, time_posted, item = Queue.Queue.get(self, block, timeout)
        return item
