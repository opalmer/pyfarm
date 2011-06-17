# No shebang line, this module is meant to be imported
#
# INITIAL: Feb 5 2011
# PURPOSE: To provide a small set of decorators for use within PyFarm.
#          Decorators are used for anything from diagnostics to debugging
#          and benchmarking.
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

import os
import sys
import time
import site
import cProfile
import warnings
import linecache
from threading import Thread
from functools import wraps

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, ".."))
site.addsitedir(root)

catch22Fail = None
TRACE_BYPASS = (
                    'string.py', 'Logger.py', 'ElementTree.py',
                    'AsyncfileSystem.py', '<string>', 'stat.py',
                    'genericpath.py', 'DebugBase.py', 'DebugClientBase.py',
                    'AsyncIO.py', 'utf_8.py', 'threading.py', 'ntpath.py',
                    'atexit.py', '__init__.py'
                )

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

            try:
                output = failValue()

            except TypeError:
                output = failValue

        finally:
            return output

    return catcher

def deprecated(func):
    '''
    Thow a warning and show information about a deprecated function
    being used
    '''

    @wraps(func)
    def new_func(*args, **kwargs):
        warnings.warn_explicit(
            "Call to deprecated function %(funcname)s." % {
                'funcname': func.__name__,
            },
            category=DeprecationWarning,
            filename=func.func_code.co_filename,
            lineno=func.func_code.co_firstlineno + 1
        )
        return func(*args, **kwargs)
    return new_func

def trace(func):
    '''Provide line by line traceback for the given function'''
    def globaltrace(frame, why, arg):
        if why == "call":
            return localtrace
        return None

    def localtrace(frame, why, arg):
        if why == "line":
            filename = frame.f_code.co_filename
            lineNum = frame.f_lineno
            basename = os.path.basename(filename)

            if basename not in TRACE_BYPASS:
                print "%s(%d): %s" % (
                                        basename, lineNum,
                                        linecache.getline(filename, lineNum)
                                      ),
        return localtrace

    def runTrace(*args, **kwargs):
        sys.settrace(globaltrace)
        result = func(*args, **kwargs)
        sys.settrace(None)
        return result

    return runTrace


def profile(func):
    '''Profile the given function and print some usefull information'''
    @wraps(func)
    def runProfile(*args, **kwargs):

        print func(*args, **kwargs), dir(func(*args, **kwargs))
        #funcProfile = cProfile.runctx(func.__code__, self.__dict__, self)
        funcProfile = None
        return funcProfile

    return runProfile

def thread(func):
    '''Wrap a function into a python thread'''
    @wraps(func)
    def runThread(*args, **kwargs):
        funcThread = Thread(target=func, args=args, kwargs=kwargs)
        funcThread.start()
        return funcThread

    return runThread

def elapsed(func):
    '''Return the time required to run a given function'''
    def run(*args, **kwargs):
        start = time.time()
        output = func(*args, **kwargs)
        elapsed = time.time()-start
        return output
    return run
