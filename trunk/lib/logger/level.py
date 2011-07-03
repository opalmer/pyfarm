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
    def __init__(self, level=None, name=None, function=None,
                    output=sys.stdout):
        self.level = None
        self.name = None
        self.function = None
        self.output = output

    # TODO: Look into using environment variables rather than modules/txt files
    @property
    def enabled(self):
        '''Evaluate and return if the level is truly enabled or not'''
        pass

    def __call__(self, message, pre="", post=os.linesep, output=None):
        '''Call the log output and show the message'''
        output = output or self.output
        formatted = message # TODO: Use formatting from the logging config
        output.write(pre+formatted+post)
