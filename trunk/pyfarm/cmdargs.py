# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
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
# You should have received a copy of the GNU Lesser General Public Lic

'''
base functions and initial setup of argument parsing for the project.
'''

import argparse

def tobool(value):
    '''converts the incoming value to a boolean'''
    if isinstance(value, bool): return value
    elif isinstance(value, (int, long, float)): return bool(value)
    elif value.lower() == "false": return False
    elif value.lower() == "true": return True
    elif value == "0": return False
    elif value == "1": return True
    else:
        raise TypeError("failed to convert %s to a boolean value" % value)
# end tobool

def tolist(value):
    '''converts the incoming value to a list'''
    if isinstance(value, (str, unicode)):
        if "," in value:
            return [ v.strip() for v in value.split(",") ]
        else:
            return [ value ]
    else:
        raise TypeError("failed to convert %s to a list" % value)
# end tolist

def printOptions(options, log):
    '''prints out the keys and values being applied to the options'''
    def _log(msg):
        log.msg(msg, system="argparse")

    for key, value in vars(options).iteritems():
        _log("%s: %s" % (key, value))
#        _log(msg)
# end printOptions

# common argument handling setup
parser = argparse.ArgumentParser()
parser.add_argument(
    '--force-kill', default=False, type=tobool,
    help='kill any currently running process before starting'
)
parser.add_argument(
    '--wait', default=False, type=tobool,
    help='waits for running processes to terminate first'
)
parser.add_argument(
    '--log', default=None,
    help='location to send the logfile to'
)
parser.add_argument(
    '--remove-lock', default=False, type=tobool,
    help='Removes the lock file on disk before starting if one exists.  This' +
         ' is mainly used if you already know the process does not exist and' +
         ' you do not wish to remove the lock file manually.'
)
