'''
HOMEPAGE: www.pyfarm.net
INITIAL: Feb 5 2011
PURPOSE: To provide a small set of decorators for use within PyFarm.
         Decorators are used for anything from diagnostics to debugging
         and benchmarking.

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys
import time
from threading import Thread
from functools import wraps

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, ".."))
MODULE = os.path.basename(__file__)

if PYFARM not in sys.path: sys.path.append(PYFARM)
from lib import Logger

log          = Logger.Logger(MODULE)
#log.disabled = True
catch22Fail  = None
def catch22(func):
    '''Catch and process all possible errors in the best possible way'''
    def catcher(*args, **kwargs):
        failValue = None
        if len(args):
            failValue = args[0].__dict__.get("catch22Fail")

        # use the default if it has been set
        if catch22Fail:
            failValue = catch22Fail

        # attempt to get and return output from func
        try:
            output = func(*args, **kwargs)

        # start processing if exception from function is caught
        except Exception, error:
            log.error("Catch 22 - %s - Failed: %s" % (func, error))

            try:
                output = failValue()

            except TypeError:
                output = failValue

        else:
            log.debug("Catch 22 - %s: Success" % func)

        finally:
            log.debug("Catch 22 - %s - Returning: %s" % (func, output))
            return output

    return catcher

def thread(func):
    '''Wrap a function into a python thread'''
    @wraps(func)
    def runThread(*args, **kwargs):
        funcThread = Thread(target=func, args=args, kwargs=kwargs)
        funcThread.start()
        return funcThread

    return runThread

def timeFunction(func):
    '''Return the time required to run a given function'''
    def run(*args, **kwargs):
        start   = time.time()
        output  = func(*args, **kwargs)
        elapsed = time.time()-start
        log.debug("timeFunction - %s ran in %fs seconds" % (func, elapsed))
        return output
    return run

if __name__ == '__main__':
    @thread
    @timeFunction
    @catch22
    def test():
        time.sleep(3)

    test()
