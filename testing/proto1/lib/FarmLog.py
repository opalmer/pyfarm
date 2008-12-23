'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 15 2008
PURPOSE: Module used to control the output of logging
'''

# TODO: Add logging to file as well as or in place of console logging
# TODO: Create SPEFIFIC logging for rendering

import string
import logging

class FarmLog(logging):
    '''General usage logginf for PyFarm'''
    def __init__(self):
        logging.__init__(self)
        self.level = None
        self.basicConfig(format=”%(levelname)10s \
                                %(asctime)s”\
                                “%(message)s”,
                                level=self.setLevel())

    def setLevel(self,  lvl='debug'):
        '''Setup the minium level of logging to echo to console'''
        self.level = string.upper(lvl)

        if self.level == 'DEBUG':
            self.basicConfig(level=self.DEBUG)
        elif self.level == 'INFO':
            self.basicConfig(level=self.INFO)
        elif self.level == 'WARN':
            self.basicConfig(level=self.WARNING)
        elif self.level == 'ERROR':
            self.basicConfig(level=self.ERROR)
        elif self.level == 'CRITICAL':
            self.basicConfig(level=self.CRITICAL)
        else:
            raise "Sorry, %s is not a valid log \
            level.  Please use debug, info, warn \
            , error, or critical instead" % \
                    self.level

        def
