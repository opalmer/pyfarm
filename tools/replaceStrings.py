#!/usr/bin/env python

import re
import os
import time
import shutil
import fnmatch
import tempfile

START      = time.time()
ROOT       = os.path.join(os.getenv('HOME'), 'pyfarm') # directory to start in
PYEXTS     = ("*.py", "*.pyw")                         # edit files with these extensions ONLY
EXCLUSIONS = ("*.git*", "*.eric*", "*.pyc", "*~")      # DO NOT enter paths matching this
SEARCH     = re.compile(r'''__(.+)__''')               # Search for __*__ but only capture what is inside ()

###################################
# See the replace function for the line replacement
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

# make replacements
lines = 0
replacements = 0
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