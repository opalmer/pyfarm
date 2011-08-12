# No shebang line, this module is meant to be imported
#
# INITIAL: Aug 11 2011
# PURPOSE: To parse and return configuration data
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
import ConfigParser
import xml.etree.ElementTree

import validate

cwd = os.path.dirname(os.path.abspath(__file__))

# global variables
CFG_ROOT = os.path.abspath(os.path.join(cwd, "cfg"))
CFG_LEVELS = os.path.join(CFG_ROOT, "levels.xml")

# load config files - so we only have to load each item once
CFG_LEVELS_XML = xml.etree.ElementTree.ElementTree().parse(CFG_LEVELS)

print "TODO: Get default handler"
print "TODO: Look into python's handler for frame inspection (logging.__init__.Logger.findCaller"
print "------^^ looks like we can get the 'current' frame by throwing an exception' ---------- ^^"

def __elements(document, root):
    '''Return a list of elements from root in the given document'''
    return document.find(root)
# END __elements

def levels():
    '''
    Return level elements as a dictionary as a dictionary
        
    NOTE: Other than function names and output this method does NOT 
          populate missing keys.
    '''
    count = 0
    levels = {}

    for level in __elements(CFG_LEVELS_XML, "LevelConfig"):
        data = {}
        
        # gather all attributes and values from level config
        for key, value in level.items():
            data[key] = value
        
        # parse name, method, and output
        name = data.get("name")
        method = data.get("method", name)
        
        # raise error for missing name
        if not name:
            raise KeyError("missing name in level config: %s" % CFG_LEVELS)            
        
        # ensure call does not have whitespace (we check both because call
        # should be user generated if name has whitespace)
        if not validate.methodName(method) and method == name:
            error = "you must provide a method for '%s', " % name
            error += "'%s()' is an invalid method name" % call
            raise ValueError(error)
        
        elif not validate.methodName(method) and not validate.methodName(call):
            raise SyntaxError("'%s()' is an invalid method method name for call" % call)
        
        # assign method name, level id, and add to levels
        data['method'] = method
        data['id'] = count
        levels[method] = data
        count += 1
        
    return levels
# END levels
        
# post-globals setup
LEVELS = levels()

if __name__ == '__main__':
    print LEVELS