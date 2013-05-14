# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

"""
base functions and initial setup of argument parsing for the project.
"""

import argparse

def tobool(value):
    """converts the incoming value to a boolean"""
    if isinstance(value, bool): return value
    elif isinstance(value, (int, long, float)): return bool(value)
    elif value.lower() in ("false", "no", "0"): return False
    elif value.lower() in ("true", "yes", "1"): return True
    else:
        raise TypeError("failed to convert %s to a boolean value" % value)
# end tobool

def tolist(value):
    """converts the incoming value to a list"""
    if isinstance(value, (str, unicode)):
        if "," in value:
            return [ v.strip() for v in value.split(",") ]
        else:
            return [ value ]
    else:
        raise TypeError("failed to convert %s to a list" % value)
# end tolist

def evalnone(value):
    """
    If the value provided can be converted to None then return None instead
    of returning a string of None
    """
    if isinstance(value, (str, unicode)) and value in ('none', 'None', 'NONE', 'null'):
        return None
    return value
# end evalnone

def printOptions(options, log):
    """prints out the keys and values being applied to the options"""
    for key, value in vars(options).iteritems():
        log("%s: %s" % (key, value))
# end printOptions

# common argument handling setup
parser = argparse.ArgumentParser()
parser.add_argument(
    '--force-kill', action='store_true',
    help='kill any currently running process before starting'
)
parser.add_argument(
    '--wait', action='store_true',
    help='waits for running processes to terminate first'
)
parser.add_argument(
    '--log', default=None,
    help='location to send the logfile to'
)
parser.add_argument(
    '--remove-lock', action='store_true',
    help='Removes the lock file on disk before starting if one exists.  This' +
         ' is mainly used if you already know the process does not exist and' +
         ' you do not wish to remove the lock file manually.'
)
parser.add_argument(
    '--port', type=int,
    help='sets the port the service should run on (default: %(default)s)'
)
parser.add_argument(
    '--db', default=None, type=tolist,
    help='Overrides the base database configuration name(s).  Entries should' +
         ' either be in csv form or a single entry string.'
)
