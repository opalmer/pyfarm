# No shebang line, this module is meant to be imported
#
# INITIAL: June 19 2011
# PURPOSE: To create an operate the logger
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

class Base(object):
    '''
    Base object to establish basic level information and settings

    @param level: The level value as an integer
    @param name: The name of the level as text
    @param function: function to call
    '''
    def __init__(self, data):
        self.data = data
        print "WARNING: level object not configured"

    @property
    def enabled(self):
        '''Evaluate and return if the level is truly enabled or not'''
        pass

    def __call__(self, message, pre="", post=os.linesep, output=None):
        '''Call the log output and show the message'''
        if not self.enabled:
            return

        formatted = pre+formatted+post

        # if an optional output device is not provided, use the
        # preconfigured device
        print output
        if not output:
            output = self.output

        # we can only 'write' to the device if it's in the list below
        if output in (sys.stdout, sys.stderr):
            output.write(formatted)
            return formatted

        # otherwise we expect to use the object as the output itself
        else:
            return output(formatted)