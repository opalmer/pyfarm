#!/usr/bin/env python
'''
HOMEPAGE: www.pyfarm.net
INITIAL: Oct 09 2010
PURPOSE: To edit a large number of text files starting at ROOT and replace
strings matching SEARCH using the replace() function.
NOTE: This script does NOT make backups of your files!!!

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

import re
import os
import time
import shutil
import fnmatch
import tempfile

CWD        = os.path.dirname(os.path.abspath(__file__))
PYFARM     = os.path.abspath(os.path.join(CWD, ".."))
MODULE     = os.path.basename(__file__)
START      = time.time()
ROOT       = os.path.join(os.getenv('HOME'), 'pyfarm') # directory to start in
PYEXTS     = ("*.py", "*.pyw")                         # edit files with these extensions ONLY
EXCLUSIONS = ("*.git*", "*.eric*", "*.pyc", "*~")      # DO NOT enter paths matching this
SEARCH     = re.compile(r'''__(.+)__''')               # Search for __*__ but only capture what is inside ()
###################################
# See the replace function for the replacement method
###################################

def isExcluded(root):
    '''Check to see if the path should be excluded'''
    for excluded in EXCLUSIONS:
        if fnmatch.fnmatch(root, excluded):
            return True
    return False

def isPyFile(filename):
    '''Check to see if the given file is a python file'''
    for extension in PYEXTS:
        if fnmatch.fnmatch(filename, extension):
            return True
    return False

def replace(regexMatch, line):
    '''Replace method replacing line with items from regexMatch'''
    return re.sub(regexMatch.group(0), regexMatch.group(1), line)

lines        = 0
replacements = 0

# make replacements
for root, dirs, files in os.walk(ROOT):
        if not isExcluded(root):
            for filename in files:
                if isPyFile(filename):
                    sourcePath  = os.path.join(root, filename)
                    source      = open(sourcePath, 'r').readlines()
                    tmpFile     = tempfile.NamedTemporaryFile(delete=False)

                    # begin processing
                    print "Reading: %s" % sourcePath

                    # iterate over each line, search matches
                    matches = 0
                    for line in source:
                        lines += 1
                        match = SEARCH.match(line)

                        # if the line matches our regular expression in SEARCH
                        # get the whole match (group 0) and replace it with
                        # the captured (group 0) text
                        # after all that, write the results to the tmp file
                        if match:
                            tmpFile.write(replace(match, line))
                            matches += 1

                        # if nothing matched, write out the original line
                        else:
                            tmpFile.write(line)

                    tmpFile.close()

                    # if we found matches inform the user and move the tmp file
                    #  over the original
                    if matches:
                        replacements += matches
                        print "...changes: %i" % matches
                        print "Moving: %s -> %s" % (tmpFile.name, sourcePath)
                        shutil.move(tmpFile.name, sourcePath)
                        print "Done: %s" % sourcePath

                    # remove the tmp file, always
                    else:
                        os.remove(tmpFile.name)

print "Made %i replacement(s) in %i lines in %f seconds" % (replacements, lines, time.time()-START)
