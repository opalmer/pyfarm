#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com || (703)725-6544
INITIAL: Jan 1 2009
PURPOSE: Used to manage threads and assoicated processes
'''

import thread
import threading
from FarmLog import FarmLog

class Operation(threading._Timer):
    def __init__(self, *args, **kwargs):
        threading._Timer.__init__(self, *args, **kwargs)
        self.setDaemon(True)
        self.log = FarmLog("ThreadFarmer.Operation()")

    def run(self):
        while True:
            self.finished.clear()
            self.finished.wait(self.interval)
            if not self.finished.isSet():
                self.function(*self.args, **self.kwargs)
            else:
                return
            self.finished.set()

class Manager(object):
    log = FarmLog("ThreadFarmer.Manager()")
    ops = []

    def add_operation(self, operation, interval, args=[], kwargs={}):
        op = Operation(interval, operation, args, kwargs)
        self.ops.append(op)
        thread.start_new_thread(op.run, ())

    def stop(self):
        for op in self.ops:
            op.cancel()
        self._event.set()

if __name__ == '__main__':
    # Print "Hello World!" every 5 seconds

    import time

    def hello():
        print "Hello"

    def world():
        print "World!"

    timer = Manager()
    timer.add_operation(hello, 2)
    timer.add_operation(world, 1)

    while True:
        time.sleep(.1)
