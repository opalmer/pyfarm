'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 12 2008
PURPOSE: Module used to run a command and gather the output
'''

class Send(object):
    '''Used to send a command to the system'''
    def __init__(self, cmd):
        self.cmd = cmd